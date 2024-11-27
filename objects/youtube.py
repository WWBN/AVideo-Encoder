import re
import ssl
import os
import json
import sys
import subprocess
import urllib.request
from datetime import datetime, timedelta

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
        "title": yt.title if yt.title else "No Title",
        "description": yt.description if yt.description else "No Description",
        "url": yt.watch_url,
        "duration_seconds": yt.length,  # Add duration in seconds
        "created_date": datetime.now().isoformat()  # Track creation time
    }
    os.makedirs(folder, exist_ok=True)
    metadata_file_path = os.path.join(folder, "metadata.json")
    with open(metadata_file_path, "w") as meta_file:
        json.dump(metadata, meta_file, indent=4)
    print(f"Metadata saved successfully to '{metadata_file_path}'.")

def save_thumbnail(yt, folder):
    """Save the highest resolution thumbnail available."""
    video_id = yt.video_id
    thumbnail_urls = [
        f"https://img.youtube.com/vi/{video_id}/maxresdefault.jpg",  # Highest resolution
        f"https://img.youtube.com/vi/{video_id}/sddefault.jpg",     # Standard definition
        f"https://img.youtube.com/vi/{video_id}/hqdefault.jpg",     # High quality
        f"https://img.youtube.com/vi/{video_id}/mqdefault.jpg",     # Medium quality
        yt.thumbnail_url  # Default thumbnail
    ]
    
    thumbnail_path = os.path.join(folder, "thumbs.jpg")
    os.makedirs(folder, exist_ok=True)
    
    for url in thumbnail_urls:
        try:
            urllib.request.urlretrieve(url, thumbnail_path)
            print(f"Thumbnail downloaded successfully to '{thumbnail_path}' from URL: {url}")
            return  # Exit the loop on success
        except Exception as e:
            print(f"Failed to download thumbnail from '{url}': {e}")
    
    print(f"Could not download any thumbnails for video '{yt.title}'.")



def download_video(yt, folder):
    video_stream = yt.streams.get_highest_resolution()
    video_path = os.path.join(folder, "video.mp4")
    yt.register_on_progress_callback(lambda stream, chunk, bytes_remaining: save_progress(stream, bytes_remaining, folder))
    video_stream.download(output_path=folder, filename="video.mp4")
    print(f"Video downloaded successfully to '{video_path}'.")

def save_progress(stream, bytes_remaining, folder):
    total_size = stream.filesize
    downloaded = total_size - bytes_remaining
    progress = {
        "total_size": total_size,
        "downloaded": downloaded,
        "progress": round((downloaded / total_size) * 100, 2)
    }
    os.makedirs(folder, exist_ok=True)
    progress_file_path = os.path.join(folder, "progress.json")
    with open(progress_file_path, "w") as progress_file:
        json.dump(progress, progress_file, indent=4)
    print(f"Progress saved to '{progress_file_path}'.")

def clean_old_folders(base_folder, days=7):
    """Delete folders older than a specified number of days."""
    now = datetime.now()
    cutoff = now - timedelta(days=days)

    for folder in os.listdir(base_folder):
        folder_path = os.path.join(base_folder, folder)
        if os.path.isdir(folder_path):
            metadata_path = os.path.join(folder_path, "metadata.json")
            if os.path.exists(metadata_path):
                try:
                    with open(metadata_path, "r") as meta_file:
                        metadata = json.load(meta_file)
                        created_date = datetime.fromisoformat(metadata.get("created_date"))
                        if created_date < cutoff:
                            print(f"Deleting folder '{folder_path}' (created on {created_date})")
                            subprocess.call(["rm", "-rf", folder_path])
                except Exception as e:
                    print(f"Error processing folder '{folder_path}': {e}")

def main():
    if len(sys.argv) < 3:
        print("Usage: python yt_downloader.py <YouTube_URL> <Folder_Name> [metadata|thumbnail|video|all]")
        sys.exit(1)

    # Get the directory where the script is located
    script_dir = os.path.dirname(os.path.abspath(__file__))
    base_folder = os.path.join(script_dir, '../videos/pytube/')
    url = sys.argv[1]
    folder_name = os.path.join(base_folder, sys.argv[2])
    action = sys.argv[3].lower() if len(sys.argv) > 3 else "video"

    os.makedirs(folder_name, exist_ok=True)

    try:
        yt = YouTube(url)
        if action == "metadata":
            save_metadata(yt, folder_name)
        elif action == "thumbnail":
            save_thumbnail(yt, folder_name)
        elif action == "video":
            download_video(yt, folder_name)
        elif action == "all":
            save_metadata(yt, folder_name)
            save_thumbnail(yt, folder_name)
            download_video(yt, folder_name)
        else:
            print("Invalid action specified. Use 'metadata', 'thumbnail', 'video', or 'all'.")
        
        # Clean old folders after the operation
        clean_old_folders(base_folder)
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    main()
