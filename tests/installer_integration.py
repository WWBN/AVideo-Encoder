"""Encoder setup regression checks using disposable files and databases only.
Run with --php, --mysql and --port; PHP must include the installer extensions.
No existing application configuration is loaded. The Streamer is a local fixture.
"""
import argparse, hashlib, http.cookiejar, http.server, json, os, re, shutil, socket, subprocess, tempfile, threading, time, urllib.error, urllib.parse, urllib.request, uuid
from pathlib import Path
parser = argparse.ArgumentParser()
parser.add_argument('--php', default='php')
parser.add_argument('--mysql', default='mysql')
parser.add_argument('--port', type=int, default=3306)
parser.add_argument('--user', default='root')
parser.add_argument('--php-arg', action='append', default=[])
args = parser.parse_args()
php = [args.php] + args.php_arg
mysql = [args.mysql, '--host=127.0.0.1', '--port='+str(args.port), '--user='+args.user, '--batch', '--skip-column-names']
repo = Path(__file__).resolve().parents[1]
databases = []
password = "test'password\\safe"
hash_value = subprocess.check_output(php+['-r', "echo md5(hash('whirlpool',sha1(stream_get_contents(STDIN))));"], input=password, text=True).strip()
class Streamer(http.server.BaseHTTPRequestHandler):
    def do_POST(self):
        data = urllib.parse.parse_qs(self.rfile.read(int(self.headers['Content-Length'])).decode())
        self.send_response(200); self.end_headers()
        self.wfile.write(json.dumps({'isAdmin': data.get('pass') == [hash_value] and data.get('encodedPass') == ['true']}).encode())
    def log_message(self, *args): pass
mock = http.server.ThreadingHTTPServer(('127.0.0.1', 0), Streamer)
threading.Thread(target=mock.serve_forever, daemon=True).start()
def sql(statement):
    return subprocess.check_output(mysql+['--execute='+statement], text=True).strip()
def new_database():
    name = 'encoder_qa_'+uuid.uuid4().hex[:14]; databases.append(name); return name
with tempfile.TemporaryDirectory(prefix='encoder-installer-test-') as directory:
    root = Path(directory)
    shutil.copytree(repo/'install', root/'install')
    for asset in ['jquery/dist/jquery.min.js','bootstrap/dist/js/bootstrap.min.js','bootstrap/dist/css/bootstrap.min.css']:
        f = root/'node_modules'/asset; f.parent.mkdir(parents=True,exist_ok=True); f.write_text('')
    (root/'videos').mkdir(); (root/'objects').mkdir()
    # Execute generated assignments and a minimal connection, without bootstrapping a real Encoder.
    (root/'objects/include_config.php').write_text("<?php $global['mysqli'] = new mysqli($mysqlHost,$mysqlUser,$mysqlPass,$mysqlDatabase,(int)$mysqlPort); echo $global['mysqli']->host_info;")
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
    log = open(root/'php.log', 'w')
    server = subprocess.Popen(php+['-S', '127.0.0.1:'+str(port), '-t', str(root)], stdout=log, stderr=log)
    url = 'http://127.0.0.1:'+str(port)+'/install/'
    client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    config = root/'videos/configuration.php'
    def payload():
        page = client.open(url).read().decode()
        assert 'Ubuntu setup help' in page and 'assets/logo.png' in page
        token = re.search(r'name="install_csrf_token" value="([^"]+)"', page).group(1)
        return dict(install_csrf_token=token, databaseHost='127.0.0.1', databasePort=str(args.port), databaseUser=args.user,
                    databasePass=os.environ.get('MYSQL_PWD',''), databaseName=new_database(), tablesPrefix='qa_', createTables='2',
                    webSiteRootURL='https://encoder.example.test:8443/sub/', siteURL='http://127.0.0.1:'+str(mock.server_port)+'/',
                    inputUser='admin', inputPassword=password, allowedStreamers='https://one.example.test/\nhttps://two.example.test/', defaultPriority='3')
    def post(data):
        try: response = client.open(url+'checkConfiguration.php', urllib.parse.urlencode(data).encode())
        except urllib.error.HTTPError as error: response = error
        body = response.read().decode(); assert password not in body and hash_value not in body
        return json.loads(body)
    try:
        for _ in range(50):
            try: client.open(url); break
            except OSError: time.sleep(.1)
        d = payload()
        for change in [dict(install_csrf_token='bad'),dict(tablesPrefix='bad`'),dict(defaultPriority='11'),dict(databasePort='0'),dict(inputPassword='wrong'),dict(allowedStreamers='javascript:bad')]:
            bad = dict(d, **change); result = post(bad); assert result['error'], result; assert not config.exists()
        print('PASS validation, CSRF, administrator verification and credential redaction')
        result = post(dict(d, action='test')); assert not result['error'], result
        assert sql("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='"+d['databaseName']+"'") == '0'
        print('PASS connection test makes no database or file changes')
        result = post(d); assert result.get('installed'), result
        assert sql('SELECT pass FROM '+d['databaseName']+'.qa_streamers') == hash_value
        assert sql('SELECT COUNT(*) FROM '+d['databaseName']+'.qa_formats') == '35'
        assert sql('SELECT defaultPriority FROM '+d['databaseName']+'.qa_configurations_encoder') == '3'
        subprocess.check_call(php+['-l',str(config)],stdout=subprocess.DEVNULL)
        runtime = subprocess.check_output(php+[str(config)], text=True); assert 'TCP/IP' in runtime
        print('PASS complete installation, password encoding, 35 formats, prefix and nondefault port')
        original = config.read_bytes(); assert post(d)['error']; assert original == config.read_bytes()
        config.write_text('<?php throw new Exception("secret sentinel");')
        page = client.open(url).read().decode()
        for secret in ['secret sentinel', str(root), 'install_csrf_token', 'php.ini', 'Ubuntu setup help', 'configurationForm']:
            assert secret not in page, secret
        assert 'Installation complete.' in page
        assert set(post({})).issubset({'error','msg','success'})
        config.unlink()
        print('PASS installed pages and endpoints disclose no setup details and do not execute configuration')
        # A second prefixed installation must coexist with both Encoder tables and unrelated application data.
        d2 = payload(); d2['databaseName'] = d['databaseName']; d2['tablesPrefix'] = 'second_'
        sql('CREATE TABLE '+d['databaseName']+'.unrelated (id INT); INSERT INTO '+d['databaseName']+'.unrelated VALUES(7)')
        result = post(d2); assert result.get('installed'), result
        assert sql('SELECT id FROM '+d['databaseName']+'.unrelated') == '7'
        assert sql('SELECT COUNT(*) FROM '+d['databaseName']+'.qa_streamers') == '1'
        config.unlink(); assert post(d2)['error']
        print('PASS multiple prefixes, unique foreign key names, existing data preserved')
        # A failed record insertion rolls back the entire initial registration and catalog.
        d3 = payload(); sql('CREATE DATABASE '+d3['databaseName'])
        schema = (root/'install/database.sql').read_text()
        broken = schema.replace('`allowedStreamersURL` TEXT NULL', '`allowedStreamersURL` VARCHAR(1) NULL')
        (root/'install/database.sql').write_text(broken)
        result = post(d3); assert result['error'] and result['stage']=='records',result; assert not config.exists()
        assert sql('SELECT COUNT(*) FROM '+d3['databaseName']+'.qa_streamers') == '0'
        assert sql('SELECT COUNT(*) FROM '+d3['databaseName']+'.qa_formats') == '0'
        sql('ALTER TABLE '+d3['databaseName']+'.qa_configurations_encoder MODIFY allowedStreamersURL TEXT NULL')
        (root/'install/database.sql').write_text(schema)
        result = post(d3); assert result.get('installed'),result; config.unlink()
        print('PASS failed initial records roll back; retry succeeds')
        for mode in ['0','1']:
            data = payload(); data['createTables'] = mode; data['tablesPrefix'] = ''
            sql('CREATE DATABASE '+data['databaseName'])
            if mode == '0':
                subprocess.run(mysql+[data['databaseName']], input=schema, text=True, check=True, stdout=subprocess.DEVNULL)
            result = post(data); assert result.get('installed'),result; config.unlink()
        print('PASS existing database and manually imported schema modes')
        # Syntax failure must never publish a configuration.
        data = payload(); (root/'install/database.sql').write_text(schema.replace('`name` VARCHAR(45)', '`name` INVALID_TYPE'))
        result = post(data); assert result['error'] and result['stage']=='tables',result; assert not config.exists()
        print('PASS SQL import failure stops before configuration publication')
        (root/'install/database.sql').write_text(schema)
        data = payload(); sql('CREATE DATABASE '+data['databaseName'])
        environment = dict(os.environ, SERVER_URL=data['webSiteRootURL'], DB_MYSQL_HOST='127.0.0.1', DB_MYSQL_PORT=str(args.port),
                           DB_MYSQL_NAME=data['databaseName'], DB_MYSQL_USER=args.user, STREAMER_URL=data['siteURL'],
                           STREAMER_USER='admin', STREAMER_PASSWORD=password)
        if os.environ.get('MYSQL_PWD'):
            environment['DB_MYSQL_PASSWORD'] = os.environ['MYSQL_PWD']
        else:
            environment.pop('DB_MYSQL_PASSWORD',None)
        run = subprocess.run(php+[str(root/'install/cli.php')],cwd=directory,env=environment,text=True,capture_output=True)
        assert run.returncode == 0,run.stdout+run.stderr
        assert config.exists() and password not in run.stdout+run.stderr
        print('PASS CLI accepts optional database password from a different working directory')

    finally:
        server.terminate(); server.wait(); log.close(); mock.shutdown(); mock.server_close()
        for name in databases: sql('DROP DATABASE IF EXISTS `'+name+'`')
