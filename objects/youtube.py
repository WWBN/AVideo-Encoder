import re
import ssl
import os
import json
import sys
import subprocess
import urllib.request

# Function to ensure pytube is installed
def ensure_pytube_installed():
    try:
        import pytube
    except ImportError:
        print("pytube is not installed. Installing it now...")
        subprocess.check_call([sys.executable, "-m", "pip", "install", "pytube"])
        import pytube
    return pytube

# Ensure pytube is available
pytube = ensure_pytube_installed()
from pytube import YouTube
from pytube.innertube import _default_clients
from pytube.exceptions import RegexMatchError

# Patching pytube for throttling
_default_clients["ANDROID"]["context"]["client"]["clientVersion"] = "19.08.35"
_default_clients["IOS"]["context"]["client"]["clientVersion"] = "19.08.35"
_default_clients["ANDROID_EMBED"]["context"]["client"]["clientVersion"] = "19.08.35"
_default_clients["IOS_EMBED"]["context"]["client"]["clientVersion"] = "19.08.35"
_default_clients["IOS_MUSIC"]["context"]["client"]["clientVersion"] = "6.41"
_default_clients["ANDROID_MUSIC"] = _default_clients["ANDROID"]

def patched_get_throttling_function_name(js: str) -> str:
    function_patterns = [
        r'a\.[a-zA-Z]\s*&&\s*\([a-z]\s*=\s*a\.get\("n"\)\)\s*&&.*?\|\|\s*([a-z]+)',
        r'\([a-z]\s*=\s*([a-zA-Z0-9$]+)(\[\d+\])?\([a-z]\)',
        r'\([a-z]\s*=\s*([a-zA-Z0-9$]+)(\[\d+\])\([a-z]\)',
    ]
    for pattern in function_patterns:
        regex = re.compile(pattern)
        function_match = regex.search(js)
        if function_match:
            if len(function_match.groups()) == 1:
                return function_match.group(1)
            idx = function_match.group(2)
            if idx:
                idx = idx.strip("[]")
                array = re.search(
                    r'var {nfunc}\s*=\s*(\[.+?\]);'.format(
                        nfunc=re.escape(function_match.group(1))),
                    js
                )
                if array:
                    array = array.group(1).strip("[]").split(",")
                    array = [x.strip() for x in array]
                    return array[int(idx)]

    raise RegexMatchError(
        caller="get_throttling_function_name", pattern="multiple"
    )

ssl._create_default_https_context = ssl._create_unverified_context
pytube.cipher.get_throttling_function_name = patched_get_throttling_function_name

def save_metadata(yt, folder):
    metadata = {
        "title": yt.title,
        "description": yt.description,
        "url": yt.watch_url
    }
    with open(os.path.join(folder, "metadata.json"), "w") as meta_file:
        json.dump(metadata, meta_file, indent=4)

def save_thumbnail(yt, folder):
    thumbnail_url = yt.thumbnail_url
    thumbnail_path = os.path.join(folder, "thumbs.jpg")
    urllib.request.urlretrieve(thumbnail_url, thumbnail_path)

def download_video(yt, folder):
    video_stream = yt.streams.get_highest_resolution()
    video_path = os.path.join(folder, "video.mp4")
    yt.register_on_progress_callback(lambda stream, chunk, bytes_remaining: save_progress(stream, bytes_remaining, folder))
    video_stream.download(output_path=folder, filename="video.mp4")

def save_progress(stream, bytes_remaining, folder):
    total_size = stream.filesize
    downloaded = total_size - bytes_remaining
    progress = {
        "total_size": total_size,
        "downloaded": downloaded,
        "progress": round((downloaded / total_size) * 100, 2)
    }
    with open(os.path.join(folder, "progress.json"), "w") as progress_file:
        json.dump(progress, progress_file, indent=4)

def main():
    if len(sys.argv) != 3:
        print("Usage: python yt_downloader.py <YouTube_URL> <Folder_Name>")
        sys.exit(1)

    url = sys.argv[1]
    folder_name = '../videos/pytube/'+sys.argv[2]

    # Create the folder
    os.makedirs(folder_name, exist_ok=True)

    try:
        # Download YouTube Video
        yt = YouTube(url)
        save_metadata(yt, folder_name)
        save_thumbnail(yt, folder_name)
        download_video(yt, folder_name)

        print(f"Download completed. Files saved in '{folder_name}'.")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    main()
