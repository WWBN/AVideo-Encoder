#!/usr/bin/env python3
"""Offline installer regressions; every privileged/network command is mocked."""
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import unittest


SCRIPT = Path(__file__).with_name("install-media-tools.sh")
WORKFLOW = SCRIPT.parent.parent / "workflows" / "tests.yml"
MIRRORS = (
    "http://azure.archive.ubuntu.com/ubuntu/\tpriority:1\n"
    "https://archive.ubuntu.com/ubuntu/\tpriority:2\n"
    "https://security.ubuntu.com/ubuntu/\tpriority:3\n"
)
MOCK = r'''
import json
import os
from pathlib import Path
import subprocess
import sys

tool = Path(sys.argv[0]).name
args = sys.argv[1:]
if tool == "sudo":
    sys.exit(subprocess.call(args))
stage = tool
if tool == "apt-get":
    stage = "update" if "update" in args else (
        "download" if "--download-only" in args else "install")
log = Path(os.environ["MOCK_LOG"])
events = [json.loads(line) for line in log.read_text().splitlines()]
call = 1 + sum(event["stage"] == stage for event in events)
mirror = Path(os.environ["APT_MIRROR_LIST"])
with log.open("a") as output:
    output.write(json.dumps({"stage": stage, "args": args,
        "mirrors": mirror.read_text() if mirror.exists() else None}) + "\n")
if f"{stage}:{call}" in os.environ.get("MOCK_FAILURES", "").split(","):
    sys.exit(124 if stage in ("update", "download") else 1)
if tool == "timeout":
    assert args[0] == "--kill-after=5s"
    sys.exit(subprocess.call(args[2:]))
if tool == "tee":
    Path(args[0]).write_text(sys.stdin.read())
'''


class MediaSetupTests(unittest.TestCase):
    def run_setup(self, failures="", mirror_exists=True):
        with tempfile.TemporaryDirectory(prefix="encoder-media-setup-") as directory:
            root = Path(directory)
            for tool in ("sudo", "timeout", "apt-get", "tee", "sleep",
                         "ffmpeg", "ffprobe", "zip", "unzip"):
                executable = root / tool
                executable.write_text(f"#!{sys.executable}\n" + MOCK)
                executable.chmod(0o755)
            mirror = root / "apt-mirrors.txt"
            if mirror_exists:
                mirror.write_text(MIRRORS)
            log = root / "commands.jsonl"
            log.touch()
            result = subprocess.run(
                ["bash", str(SCRIPT)], text=True, capture_output=True, timeout=20,
                env={**os.environ, "PATH": str(root) + os.pathsep + os.environ["PATH"],
                     "APT_MIRROR_LIST": str(mirror), "MOCK_LOG": str(log),
                     "MOCK_FAILURES": failures},
            )
            events = [json.loads(line) for line in log.read_text().splitlines()]
            return result, events, mirror.read_text() if mirror.exists() else None

    def apt_stages(self, events):
        return [event["stage"] for event in events
                if event["stage"] in ("update", "download", "install")]

    def test_success_keeps_default_mirrors_and_verifies_every_tool(self):
        result, events, mirrors = self.run_setup()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(mirrors, MIRRORS)
        self.assertEqual(self.apt_stages(events), ["update", "download", "install"])
        self.assertEqual([event["stage"] for event in events[-4:]],
                         ["ffmpeg", "ffprobe", "zip", "unzip"])
        for event in events:
            if event["stage"] in ("update", "download", "install"):
                self.assertIn("APT::Update::Error-Mode=any", event["args"])
                self.assertIn("Acquire::Retries=1", event["args"])
            if event["stage"] in ("download", "install"):
                self.assertIn("--no-install-recommends", event["args"])
                self.assertEqual(event["args"][-3:], ["ffmpeg", "zip", "unzip"])
                self.assertIn("--download-only" if event["stage"] == "download"
                              else "--no-download", event["args"])
        timeouts = [event for event in events if event["stage"] == "timeout"]
        self.assertEqual(len(timeouts), 2)  # Never time out an active dpkg install.

    def test_download_timeout_switches_mirrors_before_retrying(self):
        result, events, mirrors = self.run_setup("download:1")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(self.apt_stages(events),
                         ["update", "download", "update", "download", "install"])
        self.assertNotIn("azure.archive.ubuntu.com", mirrors)
        self.assertIn("https://archive.ubuntu.com/ubuntu/\tpriority:1", mirrors)
        self.assertIn("https://security.ubuntu.com/ubuntu/", mirrors)
        updates = [event for event in events if event["stage"] == "update"]
        self.assertEqual(updates[0]["mirrors"], MIRRORS)
        self.assertEqual(updates[1]["mirrors"], mirrors)

    def test_failed_update_does_not_download_with_stale_indexes(self):
        result, events, _ = self.run_setup("update:1")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(self.apt_stages(events),
                         ["update", "update", "download", "install"])

    def test_exhausted_retries_fail_closed_within_step_budget(self):
        result, events, _ = self.run_setup("download:1,download:2")
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(self.apt_stages(events),
                         ["update", "download", "update", "download"])
        self.assertFalse(any(event["stage"] == "ffmpeg" for event in events))
        durations = [event["args"][:2] for event in events if event["stage"] == "timeout"]
        self.assertEqual(durations, [["--kill-after=5s", "120s"],
                                     ["--kill-after=5s", "240s"]] * 2)
        network_budget = sum(int(duration[1][:-1]) + 5 for duration in durations)
        network_budget += sum(int(event["args"][0]) for event in events
                              if event["stage"] == "sleep")
        step_minutes = int(re.search(
            r"Install media tools for real FFmpeg regressions\s+timeout-minutes: (\d+)",
            WORKFLOW.read_text()).group(1))
        self.assertLessEqual(network_budget + 150, step_minutes * 60)

    def test_install_error_is_not_retried_or_hidden(self):
        result, events, _ = self.run_setup("install:1")
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(self.apt_stages(events), ["update", "download", "install"])
        self.assertFalse(any(event["stage"] == "ffmpeg" for event in events))

    def test_each_missing_or_broken_tool_fails_setup(self):
        for tool in ("ffmpeg", "ffprobe", "zip", "unzip"):
            with self.subTest(tool=tool):
                result, _, _ = self.run_setup(f"{tool}:1")
                self.assertNotEqual(result.returncode, 0)

    def test_missing_mirror_list_fails_without_repeating_same_mirror(self):
        result, events, _ = self.run_setup("download:1", mirror_exists=False)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Cannot switch", result.stderr)
        self.assertEqual(self.apt_stages(events), ["update", "download"])

    def test_mirror_write_error_is_not_ignored(self):
        result, events, mirrors = self.run_setup("download:1,tee:1")
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(mirrors, MIRRORS)
        self.assertEqual(self.apt_stages(events), ["update", "download"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
