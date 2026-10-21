<?php
// Standalone regression test, without an installed database or Streamer configuration.
// php tests/encoder-upload-authorization-regression.php
// Runs the real view/upload.php in a child process (it ends with die()) against a temporary
// directory, and checks that a refused upload never stores the file in videos/.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

if (($argv[1] ?? '') === '--child') {
    $uploadTestScenario = json_decode(base64_decode($argv[2]), true);

    class Login
    {
        public static function canUpload()
        {
            global $uploadTestScenario;
            return $uploadTestScenario['canUpload'];
        }

        public static function getStreamerId()
        {
            global $uploadTestScenario;
            return $uploadTestScenario['streamerId'];
        }
    }

    class Encoder
    {
        const AUDIO_EXTENSIONS = ['mp3'];
        const STATUS_QUEUE = 'queue';

        public function __construct($id)
        {
        }

        public function __call($name, $arguments)
        {
            if ($name === 'getNewVideosId') {
                return (object) ['response' => (object) ['video_id' => 7]];
            }
            return 1;
        }

        public static function sendFile()
        {
        }
    }

    class Streamer
    {
        public function __construct($id)
        {
        }

        public function getPriority()
        {
            return 1;
        }
    }

    class Format
    {
        public function __construct($id)
        {
        }

        public function getExtension()
        {
            return 'mp4';
        }
    }

    function cleanString($text)
    {
        return $text;
    }

    function decideFormatOrder()
    {
        return [];
    }

    $global = [
        'systemRootPath' => $uploadTestScenario['root'],
        'webSiteRootURL' => 'https://encoder.example.test/',
        'allowed' => ['mp4', 'mp3'],
    ];
    $_FILES = ['upl' => ['name' => 'clip.mp4', 'tmp_name' => $uploadTestScenario['source'], 'error' => 0]];
    if ($uploadTestScenario['forceRename']) {
        // Same variable view/jquery-file-upload/server/php/fileuploadchunkdone.php sets for chunked uploads
        $forceRename = true;
    }
    require dirname(__DIR__) . '/view/upload.php';
    exit(0);
}

function expectUpload($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

function runUploadScenario(array $scenario)
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'encoder-upload-test-' . bin2hex(random_bytes(8));
    mkdir($dir . '/videos', 0777, true);
    $source = $dir . '/source.mp4';
    file_put_contents($source, 'not a real video');

    $scenario['root'] = $dir . '/';
    $scenario['source'] = $source;
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --child ' . escapeshellarg(base64_encode(json_encode($scenario)));
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $stored = glob($dir . '/videos/original_*');
    $result = [
        'response' => json_decode($output, true),
        'stored' => $stored,
        'sourceKept' => is_file($source),
    ];

    foreach (array_merge($stored, [$source]) as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($dir . '/videos');
    rmdir($dir);
    return $result;
}

$refused = runUploadScenario(['canUpload' => false, 'streamerId' => 0, 'forceRename' => true]);
expectUpload(($refused['response']['msg'] ?? '') === 'This user can not upload files', 'Refused upload answers "This user can not upload files"');
expectUpload(empty($refused['stored']), 'Refused upload stores nothing in videos/');
expectUpload($refused['sourceKept'], 'Refused upload does not move the source file');

$direct = runUploadScenario(['canUpload' => false, 'streamerId' => 0, 'forceRename' => false]);
expectUpload(($direct['response']['msg'] ?? '') === 'This user can not upload files', 'Refused direct POST is answered before move_uploaded_file() runs');
expectUpload(empty($direct['stored']), 'Refused direct POST stores nothing in videos/');

$noStreamer = runUploadScenario(['canUpload' => true, 'streamerId' => 0, 'forceRename' => true]);
expectUpload(($noStreamer['response']['msg'] ?? '') === 'There is no streamer site', 'Upload without a Streamer answers "There is no streamer site"');
expectUpload(empty($noStreamer['stored']), 'Upload without a Streamer stores nothing in videos/');

$allowed = runUploadScenario(['canUpload' => true, 'streamerId' => 3, 'forceRename' => true]);
expectUpload(($allowed['response']['error'] ?? true) === false, 'Allowed upload is queued');
expectUpload(count($allowed['stored']) === 1 && !$allowed['sourceKept'], 'Allowed upload moves the file to videos/original_*');
expectUpload(($allowed['response']['videos_id'] ?? 0) === 7, 'Allowed upload still returns the Streamer videos_id');
