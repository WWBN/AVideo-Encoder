#!/usr/bin/env bash
# GitHub-hosted Ubuntu runner setup. Never skip the real media regressions.
set -euo pipefail

mirror_list=${APT_MIRROR_LIST:-/etc/apt/apt-mirrors.txt}
apt_options=(
  -o Acquire::Retries=1
  -o Acquire::http::Timeout=30
  -o Acquire::https::Timeout=30
  -o APT::Update::Error-Mode=any
)
packages=(ffmpeg zip unzip)

# Bound only network work: interrupting dpkg midway can break the next attempt.
# Two attempts take at most 2 * (120 + 5 + 240 + 5) + 10 = 750 seconds,
# leaving 150 seconds of the workflow's 15-minute step for local installation.
for attempt in 1 2; do
  if sudo timeout --kill-after=5s 120s apt-get "${apt_options[@]}" update \
    && sudo timeout --kill-after=5s 240s apt-get "${apt_options[@]}" \
      install --yes --no-install-recommends --download-only "${packages[@]}"; then
    break
  fi

  if [ "$attempt" = 2 ]; then
    echo 'Media dependency downloads failed on both attempts.' >&2
    exit 1
  fi

  # Hosted runners prefer Azure even when it is transferring too slowly to
  # trigger APT's idle timeout. A new attempt must not select that mirror again.
  # Keep the runner's suites, components and signature checks unchanged.
  if [ ! -f "$mirror_list" ]; then
    echo "Cannot switch the runner's Ubuntu mirrors: $mirror_list is missing." >&2
    exit 1
  fi
  echo 'Retrying media dependency downloads using the official Ubuntu archives.'
  printf 'https://archive.ubuntu.com/ubuntu/\tpriority:1\nhttps://security.ubuntu.com/ubuntu/\tpriority:2\n' \
    | sudo tee "$mirror_list" >/dev/null
  sleep 10
done

# Reuse the verified downloads, with no second network phase or ignored errors.
sudo apt-get "${apt_options[@]}" install --yes --no-install-recommends --no-download "${packages[@]}"
ffmpeg -version
ffprobe -version
zip -v
unzip -v
