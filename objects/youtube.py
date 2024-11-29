import re
import ssl
import os
import json
import sys
import subprocess
import urllib.request
from datetime import datetime, timedelta
import http.client
import socket
import concurrent.futures

http.client.HTTPConnection.debuglevel = 0  # Set to 1 for verbose HTTP output

# Define the proxy list once at the top
PROXIES = [
    'http://68.178.168.41:80',
    'http://148.72.165.171:10501',
    'http://47.242.47.64:8888',
    'http://200.174.198.86:8888',
    'http://13.208.56.180:80',
    'http://5.252.22.45:80',
    'http://20.111.54.16:8123',
    'http://157.254.53.50:80',
    'http://54.152.3.36:80',
    'http://13.36.113.81:3128'
]


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

# Add a User-Agent header to urllib requests
def add_user_agent():
    user_agent = ("Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                  "AppleWebKit/537.36 (KHTML, like Gecko) "
                  "Chrome/94.0.4606.61 Safari/537.36")
    headers = [
        ("User-Agent", user_agent),
        ("Accept-Language", "en-US,en;q=0.9"),
        ("Accept", "*/*"),
        ("Connection", "keep-alive"),
    ]
    opener = urllib.request.build_opener()
    opener.addheaders = headers
    urllib.request.install_opener(opener)

# Ensure User-Agent is applied
add_user_agent()

def set_proxy(proxy_url):
    proxy_handler = urllib.request.ProxyHandler({'http': proxy_url, 'https': proxy_url})
    opener = urllib.request.build_opener(proxy_handler)
    opener.addheaders = urllib.request.build_opener().addheaders  # Keep existing headers
    urllib.request.install_opener(opener)
    # Also set the proxy for pytube
    pytube.request.default_proxy = proxy_url

def reset_proxy():
    # Remove the proxy settings
    urllib.request.install_opener(urllib.request.build_opener())
    pytube.request.default_proxy = None

def log_system_details():
    print("Logging system details:")
    print(f"Python version: {sys.version}")
    print(f"Pytube version: {pytube.__version__}")
    print(f"SSL version: {ssl.OPENSSL_VERSION}")
    print(f"System platform: {sys.platform}")

def save_progress(stream, chunk, bytes_remaining, folder):
    try:
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
    except Exception as e:
        print(f"Error saving progress: {e}")

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

def get_metadata_safe(yt):
    """Safely retrieve metadata from YouTube object."""
    metadata = {}

    def fetch_metadata():
        try:
            metadata["title"] = yt.title if hasattr(yt, "title") and yt.title else "Unknown Title"
            metadata["description"] = yt.description if hasattr(yt, "description") and yt.description else "No Description"
            metadata["url"] = yt.watch_url if hasattr(yt, "watch_url") else "Unknown URL"
            metadata["duration_seconds"] = yt.length if hasattr(yt, "length") else 0
            return True  # Indicate success
        except Exception as e:
            print(f"Error retrieving metadata: {e}")
            return False

    # Try fetching metadata directly, fallback to proxies if needed
    success = attempt_with_proxies(fetch_metadata)
    if not success:
        print("Failed to retrieve metadata with all proxies.")
        metadata = {
            "title": "Unknown Title",
            "description": "No Description",
            "url": "Unknown URL",
            "duration_seconds": 0,
        }
    return metadata

def save_metadata(yt, folder):
    """Save metadata with fallback."""
    try:
        metadata = get_metadata_safe(yt)  # Proxy-enabled metadata retrieval
        metadata["created_date"] = datetime.now().isoformat()  # Track creation time
        os.makedirs(folder, exist_ok=True)
        metadata_file_path = os.path.join(folder, "metadata.json")
        with open(metadata_file_path, "w") as meta_file:
            json.dump(metadata, meta_file, indent=4)
        print(f"Metadata saved successfully to '{metadata_file_path}'.")
        return True
    except Exception as e:
        print(f"Error saving metadata: {e}")
        return False

def save_thumbnail(yt, folder):
    """Save the highest resolution thumbnail available, with fallback handling."""
    try:
        video_id = yt.video_id
        thumbnail_urls = [
            f"https://img.youtube.com/vi/{video_id}/maxresdefault.jpg",  # Highest resolution
            f"https://img.youtube.com/vi/{video_id}/sddefault.jpg",     # Standard definition
            f"https://img.youtube.com/vi/{video_id}/hqdefault.jpg",     # High quality
            f"https://img.youtube.com/vi/{video_id}/mqdefault.jpg",     # Medium quality
            yt.thumbnail_url if hasattr(yt, "thumbnail_url") else None  # Default thumbnail
        ]

        thumbnail_urls = [url for url in thumbnail_urls if url]  # Remove None entries
        thumbnail_path = os.path.join(folder, "thumbs.jpg")
        os.makedirs(folder, exist_ok=True)

        for url in thumbnail_urls:
            try:
                urllib.request.urlretrieve(url, thumbnail_path)
                print(f"Thumbnail downloaded successfully to '{thumbnail_path}' from URL: {url}")
                return True  # Exit the loop on success
            except Exception as e:
                print(f"Failed to download thumbnail from '{url}': {e}")

        print(f"Could not download any thumbnails for video '{yt.title}'.")
        return False
    except Exception as e:
        print(f"Error in save_thumbnail: {e}")
        return False

def download_video(yt, folder):
    """Download the video at the highest resolution, with merging audio and video."""
    try:
        # Get the highest resolution video-only stream
        video_stream = yt.streams.filter(adaptive=True, type="video").order_by('resolution').desc().first()
        # Get the highest quality audio-only stream
        audio_stream = yt.streams.filter(adaptive=True, type="audio").order_by('abr').desc().first()
        
        if video_stream is None or audio_stream is None:
            print("No suitable streams available to download.")
            return False
        
        print(f"Selected video stream: {video_stream}")
        print(f"Selected audio stream: {audio_stream}")
        
        video_path = os.path.join(folder, "video.mp4")
        audio_path = os.path.join(folder, "audio.mp4")
        output_path = os.path.join(folder, "merged_video.mp4")
        
        # Download video and audio streams
        video_stream.download(output_path=folder, filename="video.mp4")
        audio_stream.download(output_path=folder, filename="audio.mp4")
        
        print("Downloaded video and audio streams. Merging them now...")
        
        # Merge video and audio using ffmpeg
        merge_command = [
            'ffmpeg',
            '-y',  # Overwrite output file if it exists
            '-i', video_path,
            '-i', audio_path,
            '-c', 'copy',
            output_path
        ]
        subprocess.run(merge_command, check=True)
        
        print(f"Video merged successfully to '{output_path}'.")
        # Optionally, remove the separate video and audio files
        os.remove(video_path)
        os.remove(audio_path)
        return True
    except Exception as e:
        print(f"Error downloading or merging video: {e}")
        return False

def attempt_with_proxies(function, *args):
    # First attempt without proxy
    reset_proxy()
    try:
        with concurrent.futures.ThreadPoolExecutor() as executor:
            future = executor.submit(function, *args)
            success = future.result(timeout=10)
        if success:
            return True
    except Exception as e:
        print(f"Function failed without proxy: {e}")
    except concurrent.futures.TimeoutError:
        print("Function timed out without proxy")

    for proxy in PROXIES:
        print(f"Retrying with proxy {proxy}")
        try:
            set_proxy(proxy)
            with concurrent.futures.ThreadPoolExecutor() as executor:
                future = executor.submit(function, *args)
                success = future.result(timeout=10)
            if success:
                return True
        except concurrent.futures.TimeoutError:
            print(f"Proxy {proxy} timed out")
        except Exception as e:
            print(f"Failed with proxy {proxy}: {e}")
        finally:
            reset_proxy()
    print("All proxies failed.")
    return False

def main():
    if len(sys.argv) < 3:
        print("Usage: python yt_downloader.py <YouTube_URL> <Folder_Name> [metadata|thumbnail|video|all]")
        sys.exit(1)

    # Get the directory where the script is located
    script_dir = os.path.dirname(os.path.abspath(__file__))
    base_folder = os.path.join(script_dir, '../videos/pytube/')
    url = sys.argv[1]
    folder_name = os.path.join(base_folder, sys.argv[2])
    action = sys.argv[3].lower() if len(sys.argv) > 3 else "all"

    os.makedirs(folder_name, exist_ok=True)

    try:
        add_user_agent()  # Ensure all requests include a user-agent
        log_system_details()  # Log environment details
        print(f"Attempting to access YouTube video: {url}")

        yt = None

        def create_youtube_object():
            nonlocal yt
            yt = YouTube(url)
            return True

        success = attempt_with_proxies(create_youtube_object)

        if not success:
            print("Failed to access YouTube video with all proxies.")
            sys.exit(1)

        if action == "metadata":
            attempt_with_proxies(save_metadata, yt, folder_name)
        elif action == "thumbnail":
            attempt_with_proxies(save_thumbnail, yt, folder_name)
        elif action == "video":
            attempt_with_proxies(download_video, yt, folder_name)
        elif action == "all":
            attempt_with_proxies(save_metadata, yt, folder_name)
            attempt_with_proxies(save_thumbnail, yt, folder_name)
            attempt_with_proxies(download_video, yt, folder_name)
        else:
            print("Invalid action specified. Use 'metadata', 'thumbnail', 'video', or 'all'.")

        clean_old_folders(base_folder)
    except Exception as e:
        print(f"Error encountered during processing: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()
