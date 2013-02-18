<?php
//See https://github.com/jrk/passfs/blob/master/opts.c for the C equivalent of this file
if (!extension_loaded("fuse"))
    dl("fuse.so");
error_reporting(E_ALL);
class Sample4Fuse extends Fuse {
    public $root = "";
    public $stats_enabled = false;
    public function __construct() {
        //The enum at line 27, as PHP doesn't know enums, use $i as hack
        //Do not forget $i++, else opt_parse will whine.
        $i                 = 0;
        $this->argopt_keys = array(
            "KEY_STATS" => $i++,
            "KEY_HELP" => $i++,
            "KEY_FUSE_HELP" => $i++,
            "KEY_VERSION" => $i++,
            "KEY_DEBUG" => $i++,
            "KEY_MONITOR" => $i++,
            "KEY_MONITOR_FILE" => $i++,
            "KEY_DIR_METHOD2" => $i++,
            "KEY_DEMO_INT" => $i++,
            "KEY_DEMO_STRING" => $i++,
            "KEY_DEMO_SPACE" => $i++
        );
        $this->argopts     = array(
            "--help" => $this->argopt_keys["KEY_HELP"],
            "--version" => $this->argopt_keys["KEY_VERSION"],
            "-h" => $this->argopt_keys["KEY_HELP"],
            "-H" => $this->argopt_keys["KEY_FUSE_HELP"],
            "-V" => $this->argopt_keys["KEY_VERSION"],
            "stats" => $this->argopt_keys["KEY_STATS"],
            "-d" => $this->argopt_keys["KEY_DEBUG"],
            "-m" => $this->argopt_keys["KEY_MONITOR"],
            "-m=" => $this->argopt_keys["KEY_MONITOR_FILE"],
            "-D" => $this->argopt_keys["KEY_DIR_METHOD2"],
            "-n " => $this->argopt_keys["KEY_DEMO_SPACE"]
        );
        $this->dataopts    = array(
            array(
                "templ" => "-s=%s",
                "value" => "foobar",
                "key" => $this->argopt_keys["KEY_DEMO_STRING"]
            ),
            array(
                "templ" => "-i=%lu",
                "value" => 1337,
                "key" => $this->argopt_keys["KEY_DEMO_INT"]
            )
        );
    }
    public function main($argc, $argv) {
        printf("main begin: argc is %d, argv is ('%s')\n", $argc, implode("', '", $argv));
        $res = $this->opt_parse($argc, $argv, $this->dataopts, $this->argopts, array(
            $this,
            "opt_proc"
        ));
        if ($res === false) {
            printf("Error in opt_parse\n");
            exit;
        }
        printf("main end: argc now %d, argv now ('%s'), dataopts:\n%s\n", $argc, implode("', '", $argv), print_r($this->dataopts, true));
    }
    
    public function opt_proc(&$data, $arg, $key, &$argc, &$argv) {
        // return -1 to indicate error, 0 to accept parameter,1 to retain parameter and pase to FUSE
        printf("opt_proc called. arg is '%s', key is %d, argc is %d, argv is ('%s'), data is\n%s\n", $arg, $key, $argc, implode("', '", $argv), print_r($data, true));
        switch ($key) {
            case FUSE_OPT_KEY_NONOPT:
                if ($this->root == "") {
                    $this->root = $arg;
                    return 0;
                } else
                    return 1;
                break;
            case $this->argopt_keys["KEY_STATS"]:
                $this->stats_enabled = 1;
                return 0;
                break;
            case $this->argopt_keys["KEY_FUSE_HELP"]:
                array_push($argv, "-ho");
                $argc++;
            case $this->argopt_keys["KEY_HELP"]:
                printf("php-fusepassfs
by John Cobb <j.c.cobb@qmul.ac.uk>, adapted to PHP by Marco Schuster <marco@m-s-d.eu>

FUSE demo

Usage: %s [options] root_path mountpoint

Options:
    -o opt,[opt...]           mount options
    -h --help                 this help
    -H                        more help
    -V --version              print version info

Options specific to php-fusepassfs:
    -m                        monitor to STDOUT
    -m=file                   monitor to file
    -D                        implement use of offset in readdir interface
    -o stats                  show statistics in the file 'stats' under the mountpoint
", $argv[0]);
                return 0;
                break;
            case $this->argopt_keys["KEY_VERSION"]:
                printf("php-fusepassfs v. 0.1.3.3.7\n");
                return 1;
                break;
            case $this->argopt_keys["KEY_DEBUG"]:
                printf("debug mode enabled\n");
                return 1;
                break;
            case $this->argopt_keys["KEY_MONITOR"]:
                printf("monitor in fg\n");
                array_push($argv, "-f");
                $argc++;
                $data[0] = "luuulz";
                return 0;
                break;
            case $this->argopt_keys["KEY_DIR_METHOD2"]:
                printf("dirmethod 2\n");
                $data[1]                   = 31337;
                $this->use_readdir_method2 = true;
                return 0;
                break;
            case $this->argopt_keys["KEY_MONITOR_FILE"]:
                printf("monitor to file '%s'\n", $arg);
                return 0;
                break;
            case $this->argopt_keys["KEY_DEMO_SPACE"]:
                printf("demonstration parameter -n value=%s\n", $arg);
                return 0;
                break;
            default:
                return 1;
        }
    }
    public function getdir($path, &$retval) {
        if ($path != "/") {
            return -FUSE_ENOENT;
        }
        $retval["."]        = array(
            'type' => FUSE_DT_DIR
        );
        $retval[".."]       = array(
            'type' => FUSE_DT_DIR
        );
        $retval["test.txt"] = array(
            'type' => FUSE_DT_REG
        );
        return 0;
    }
    
    public function getattr($path, &$st) {
        $st['dev']     = 0;
        $st['ino']     = 0;
        $st['mode']    = 0;
        $st['nlink']   = 0;
        $st['uid']     = 0;
        $st['gid']     = 0;
        $st['rdev']    = 0;
        $st['size']    = 0;
        $st['atime']   = 0;
        $st['mtime']   = 0;
        $st['ctime']   = 0;
        $st['blksize'] = 0;
        $st['blocks']  = 0;
        
        if ($path == "/") {
            $st['mode']  = FUSE_S_IFDIR | 0775;
            $st['nlink'] = 3;
            $st['size']  = 0;
        } else if ($path == "/test.txt") {
            $st['mode']  = FUSE_S_IFREG | 0664;
            $st['nlink'] = 1;
            $st['size']  = 12;
        }
        
        return 0;
    }
    public function open($path, $mode) {
        return 1;
    }
    
    public function read($path, $fh, $offset, $buf_len, &$buf) {
        $buf = "hello world\n";
        return strlen($buf);
    }
    
    public function release($path, $fh) {
        return 0;
    }
}


$fuse = new Sample4Fuse();
$fuse->main($argc, $argv);
?>
