#!/usr/bin/perl -w
##############################################################################
## dcm.pl
## Written by: Brandon Zehm <caspian@dotconf.net>
##
## License:
##  dcm.pl (hereafter referred to as "program") is free software;
##  you can redistribute it and/or modify it under the terms of the GNU General
##  Public License as published by the Free Software Foundation; either version
##  2 of the License, or (at your option) any later version.
##  Note that when redistributing modified versions of this source code, you
##  must ensure that this disclaimer and the above coder's names are included
##  VERBATIM in the modified code.
##
## Disclaimer:
##  This program is provided with no warranty of any kind, either expressed or
##  implied.  It is the responsibility of the user (you) to fully research and
##  comprehend the usage of this program.  As with any tool, it can be misused,
##  either intentionally (you're a vandal) or unintentionally (you're a moron).
##  THE AUTHOR(S) IS(ARE) NOT RESPONSIBLE FOR ANYTHING YOU DO WITH THIS PROGRAM
##  or anything that happens because of your use (or misuse) of this program,
##  including but not limited to anything you, your lawyers, or anyone else
##  can dream up.  And now, a relevant quote directly from the GPL:
##
## NO WARRANTY
##
##  11. BECAUSE THE PROGRAM IS LICENSED FREE OF CHARGE, THERE IS NO WARRANTY
##  FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE LAW.  EXCEPT WHEN
##  OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR OTHER PARTIES
##  PROVIDE THE PROGRAM "AS IS" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESSED
##  OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
##  MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.  THE ENTIRE RISK AS
##  TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU.  SHOULD THE
##  PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY SERVICING,
##  REPAIR OR CORRECTION.
##
## The GNU GPL can be found at http://www.fsf.org/copyleft/gpl.html
##
##############################################################################
##
## Description
##
## dcm.pl (Data and Command Module) is a wrapper around the "modules" available via
## the ONA system.  It is designed to provide a console interface to all of those functions.
##
##############################################################################
##
##  Changelog:
##
##      xx/xx/2006 - v1.16 - Brandon Zehm
##          -
##
##      05/24/2006 - v1.15 - Brandon Zehm
##          - Allow stand-alone key's on the command line
##
##      04/18/2006 - v1.14 - Brandon Zehm
##          - Pass unix username to the module being run
##
##      01/03/2006 - v1.13 - Brandon Zehm
##          - Print "NO SSL" error message to STDERR
##
##      11/17/2005 - v1.12 - Brandon Zehm
##          - Add option for displaying & switching ona contexts
##
##      11/11/2005 - v1.11 - Brandon Zehm
##          - Add "create symlinks" option
##
##      11/08/2005 - v1.10 - Brandon Zehm
##          - Fixed a few syntax issues
##
##      10/26/2005 - v1.09 - Brandon Zehm
##          - Allow value in key=value pairs to be the name of an
##            input file, or - for STDIN.
##
##      10/25/2005 - v1.08 - Brandon Zehm
##          - Use HTTP POST method for sending "options" to support
##            large data uploads.
##
##      10/24/2005 - v1.07 - Brandon Zehm
##          - Added support for reading a configuration file
##
##      10/21/2005 - v1.06 - Brandon Zehm
##          - Added support for HTTP/1.1 chunked transfer encoding
##            for receiving large amounts of data.
##
##      10/18/2005 - v1.05 - Brandon Zehm
##          - Added SSL support - SSL is now used automatically if
##            the perl IO::Socket::SSL module is available.
##          - Added support to symlink this script to a module's
##            name and run that module automatically.
##
##      09/15/2005 - v1.04 - Brandon Zehm
##          - Remove -o option.  Assume everything after the module
##            name are key=value options for that module until a
##            hyphen (-) is the first character of an option.
##          - Added search for inc_standard.pl
##
##      09/08/2005 - v1.03 - Brandon Zehm
##          - Read function exit status from result, and exit with
##            appropriate error code.
##
##      08/29/2005 - v1.02 - Brandon Zehm
##          - Fixed a few "use of uninitialized variable" errors.
##
##      08/22/2005 - v1.01 - Brandon Zehm
##          - Fixed Time::HiRes conditional loading
##          - Moved some more initialization code to functions.pl
##
##      08/17/2005 - v1.00 - Brandon Zehm
##          - Final v1.00 released
##
##############################################################################
use strict;
use IO::Socket;

## Load Time::HiRes if it's available
eval { require Time::HiRes; };
unless ($@) {
    Time::HiRes->import qw(time);
}


## Include code from standardized code library: functions.pl
## Look in a few places
if ($0 =~ /^(.*\/)[^\/]/ and -f "$1/inc_standard.pl") {
    $_ = do "$1/inc_standard.pl";
}
elsif (-f "/lib/perl/inc_standard.pl") {
    $_ = do "/lib/perl/inc_standard.pl";
}
elsif (-f "/var/www/htdocs/ona/include/inc_standard.pl") {
    $_ = do "/var/www/htdocs/ona/include/inc_standard.pl";
}
else {
    die "ERROR => Standard perl library couldn't be loaded!\n";
}

## Quit if we couldn't load the library!
if ($@)  { die "ERROR => couldn't parse standard perl library: $@"; }
if (!$_) { die "ERROR => couldn't load standard perl library: $!"; }


## Import global variables from functions.pl
our %conf;
our %logging;
our %self;
our $debug;


##
## CONFIGURATION VARIABLES
## The defaults set here are only used if they are not defined in
## a configuration file or on the command line.  Use "our" rather
## than "my" to declare super-global variables that need to be
## accessed and modified in the functions loaded from other files.
##


## General configuration settings
$conf{'version'}            = '1.16';                          ## The version of this program
$conf{'authorName'}         = 'Brandon Zehm';                  ## Author's Name
$conf{'authorEmail'}        = 'caspian@dotconf.net';           ## Author's Email Address
$conf{'configurationFile'}  = 'dcm.conf';                      ## Default configuration file
$conf{'ona_context'}       = '';                               ## Default ona_context (sent to module_run.php)
$conf{'unix_username'}      = getlogin || getpwuid($<);        ## Current user's unix username (sent to module_run.php)

## Networking options
my %networking = (

    'webHost'                => 'localhost',                   ## dcm's web server
    'webPort'                => 80,                            ## Web server's port
    'useSSL'                 => 0,                             ## Use SSL for connections (0=no, 1=yes)
    'user_agent'             => 'dcm-module-interface',    ## User agent used in HTTP request
    'timeout'                => 360,                           ## Timeout in seconds for any/all connections

);


## This hash contains the key=value pairs of data being passed to
## the module the user chooses to run.
my %opt = ();



## Load IO::Socket::SSL if it's available
eval { require IO::Socket::SSL; };
## If it loaded, set the webPort to 443, and useSSL to 1.
unless ($@) {
    $networking{'webPort'} = 443;
    $networking{'useSSL'} = 1;
}












#########################################################
## SUB: help
##
## hehe, for all those newbies ;)
#########################################################
sub help {
print <<EOM;

$conf{'colorBold'}$conf{'programName'}-$conf{'version'} by $conf{'authorName'} <$conf{'authorEmail'}>$conf{'colorNoBold'}

Usage:
  $conf{'programName'} <action> [options]

$conf{'colorRed'}  Actions: $conf{'colorNormal'}
    --list                  list available modules
    --list-contexts         list available ona contexts
    --symlink               create symlinks of each module name to dcm.pl
    -r MODULE [K=V] ...     run specified module with KEY=VALUE options
                              VALUE may be a filename, or - for STDIN

$conf{'colorGreen'}  General Options: $conf{'colorNormal'}
    --context NAME          use specified ona context
    -c FILE                 configuration file [$conf{'configurationFile'}]
    -v                      verbosity - use multiple times for greater effect

$conf{'colorGreen'}  Logging Options: $conf{'colorNormal'}
    --nostdout              don't print messages to STDOUT
    --syslog                syslog all messages
    --logfile=FILE          log all messages to the specified file

EOM
exit(1);
}















######################################################################
##  Function: initialize ()
##
##  Does all the script startup jibberish.
##
######################################################################
sub initialize {

    ## Set STDOUT to flush immediatly
    $| = 1;

    ## Intercept signals
    $SIG{'QUIT'}  = sub { quit("EXITING: Received SIG$_[0]", 1); };
    $SIG{'INT'}   = sub { quit("EXITING: Received SIG$_[0]", 1); };
    $SIG{'KILL'}  = sub { quit("EXITING: Received SIG$_[0]", 1); };
    $SIG{'TERM'}  = sub { quit("EXITING: Received SIG$_[0]", 1); };

    $SIG{'HUP'}   = sub { quit("EXITING: Received SIG$_[0]", 1); };
    $SIG{'ALRM'}  = sub { quit("EXITING: Received SIG$_[0]", 1); };

    ## Let children exit on their own so the parent doesn't have to reap them.
    ## $SIG{'CHLD'}  = 'IGNORE';

    return(0);
}











######################################################################
##  Function: processCommandLine ()
##
##  Processes command line storing important data in global var %conf
##
######################################################################
sub processCommandLine {


    ############################
    ##  Process command line  ##
    ############################

    ## If this is a symlink to dcm.pl, the name of the symlink is used
    ## as the name of the module to run.
    if ($conf{'programName'} ne 'dcm.pl') {
        $self{'module'} = $conf{'programName'};
    }

    my @ARGS = @ARGV;
    my $numargv = @ARGS;
    help() unless ($numargv or $self{'module'});
    my $counter = 0;
    for ($counter = 0; $counter < $numargv; $counter++) {

        ## ACTION: run specified module (and get options for the module)
        if ( ($ARGS[$counter] eq '-r') or  ($self{'module'} and $ARGS[$counter] !~ /^-/o) ) {

            ## Unless a module is already specified, get it
            unless ($self{'module'}) {
                $counter++;
                $self{'module'} = $ARGS[$counter];
                $counter++;
            }

            ## Loop through each option passed after the module name
            while ($ARGS[$counter] && $ARGS[$counter] !~ /^-/o) {
                if ($ARGS[$counter] =~ /(\S+)=(\S.*)/) {
                    $opt{$1} = $2;
                    printmsg("DEBUG => Assigned module option key/value: $1 => $2", 3);
                }
                elsif ($ARGS[$counter] =~ /(\S+)=$/) {
                    $opt{$1} = '';
                    printmsg("DEBUG => Assigned module option key/value: $1 => ''", 3);
                }
                elsif ($ARGS[$counter] =~ /(\S+)/) {
                    $opt{$1} = 'Y';
                    printmsg("DEBUG => Assigned module option key/value: $1 => Y", 3);
                }
                else {
                    ## I can't think of why this would ever happen, but whatever... ;)
                    printmsg("WARNING => Key/Value pair [$ARGS[$counter]] is not properly formatted", 0);
                    printmsg("WARNING => Module arguments should be in the form of \"key=value\"", 0);
                }
                $counter++;
            }   $counter--;
        }

        ## ACTION: list available modules
        elsif ($ARGS[$counter] eq '--list') {
            $self{'module'} = 'get_module_list';
            $opt{'type'} = 'string';
        }

        ## ACTION: list available ONA contexts
        elsif ($ARGS[$counter] eq '--list-contexts') {
            $self{'module'} = 'ona_context_display';
            $opt{'type'} = 'string';
        }

        ## ACTION: create symlinks
        elsif ($ARGS[$counter] eq '--symlink') {
            $self{'module'} = 'get_module_list';
            $opt{'type'} = 'perl';
        }

        ## context switching (for ona)
        elsif ($ARGS[$counter] eq '--context') {
            $counter++;
            $conf{'ona_context'} = $ARGS[$counter];
        }

        ## configuration file
        elsif ($ARGS[$counter] eq '-c') {
            $counter++;
            $conf{'configurationFile'} = $ARGS[$counter];
            if (! -e $conf{'configurationFile'}) {
                printmsg("WARNING => Configuration file specified, $conf{'configurationFile'} , doesn't exist!", 0);
            }
        }

        ## verbosity
        elsif ($ARGS[$counter] =~ /^-v+/io) {
            $debug += (length($&) - 1);
        }

        ## syslog is enabled
        elsif ($ARGS[$counter] =~ /^--syslog/i) {
            $logging{'syslog'} = 1;
        }

        ## nostdout
        elsif ($ARGS[$counter] =~ /^--nostdout/i) {
            $logging{'stdout'} = 0;
        }

        ## log file
        elsif ($ARGS[$counter] =~ /^--logfile=(.*)/i) {
            $logging{'logFile'} = $1;
        }

        ## help
        elsif ($ARGS[$counter] =~ /^-h$|^--help/) {
            help();
        }

        else {
            printmsg("\"$ARGS[$counter]\" is not a recognised option!", 0);
            quit("", 1);
        }

    }


    ## Print help if we're not becoming a daemon.
    if (!$self{'module'}) {
        quit("WARNING => No action was specified, try --help!",1);
    }

    ###################################################
    ##  Input validation
    ###################################################

    ## Open the log file if we need to
    if ($logging{'logFile'}) {
        if (openLogFile($logging{'logFile'})) {
            printmsg("WARNING => Log file could not be opened!", 0);
        }
    }

    ## Return 0 errors
    return(0);
}












###############################################################################################
## FUNCTION:
##   urlencode ( string $str )
##
##
## DESCRIPTION:
##   Encodes $str for use in an URL call
##
## Example:
##   $str = urlencode("file=/etc/passwd&debug=3");
##
###############################################################################################
sub urlencode {
    ## Get incoming variables
    my ($str) = @_;
    $str =~ s/([^A-Za-z0-9])/sprintf("%%%02X", ord($1))/sego;
    return($str);
}












###############################################################################################
## FUNCTION:
##   urldecode ( string $str )
##
##
## DESCRIPTION:
##   Encodes $str for use in an URL call
##
## Example:
##   $str = urldecode("file=/etc/passwd&debug=3");
##
###############################################################################################
sub urldecode {
    ## Get incoming variables
    my ($str) = @_;
    $str =~ s/\%([A-Fa-f0-9]{2})/pack('C', hex($1))/sego;
    return($str);
}












## getline($socketRef)
sub getline {
    my ($socket) = @_;
    local ($/) = "\r\n";
    return readline *{$$socket};
}










######################################################################
## Function: getPage(string $URI, string $post, bool $return_headers, $socket)
##
## DESCRIPTION:
##     Generates a web request out of the URI, sends it to the
##     ALREADY setup network connection on $self{webHost}, reads
##     the response, and returns the result.
##
## INPUT:
##     $URI is a url, it should not contain http:// or www.blah.com,
##     it shoul look like this: /site/index.html?name=value&blah=test
##     The string should already have been urlencode()'d.
##
##     $return_headers indicates that we should return the http
##     headers with the result.  1 enables, 0 disables (default).
##
##     $socket is a reference to the socket which should be already
##     connected to a web server.
##
## OUTPUT:
##     Returns a string containing the web server's response.
##
######################################################################
sub getPage {

    ## Get incoming variables
    my ($uri, $post, $return_headers, $socket) = @_;
    chomp $uri;

    ## Generate Request
    my $request = "";
    my $method = "GET";
    if ($post) { $method = "POST"; }

    $request =  "$method $uri HTTP/1.1\r\n" .
                "Accept: \*\/\*\r\n" .
                "Host: $networking{'webHost'}\r\n" .
                "User-Agent: $networking{'user_agent'} $conf{'programName'}-$conf{'version'}\r\n" .
                "Connection: close\r\n";
    $request .= "Authorization: Basic $conf{'basicAuth'}\r\n" if ($conf{'basicAuth'});
    if ($post) {
        $request .= "Content-type: application/x-www-form-urlencoded\r\n" ;
        $request .= "Content-length: " . length($post) . "\r\n";
    }
    $request .= "\r\n";
    if ($post) {
        $request .= $post;
    }

    ## Request Page
    if ($debug >= 4) {
        print "Sending HTTP Request:\n", $request;
    }
    print {$$socket} ($request);

    ## Read the HTTP response
    my $foundAllHeaders = 0;
    my $headers = '';
    my $data = '';
    my $contentEncoding = 'plain';
    my $contentLength = 0;
    my $contentRemaining = 0;

    ## First read the headers and extract some important information
    while ($foundAllHeaders == 0) {
        ## Read a line
        my $line = getline($socket);

        ## Quit if the read failed
        if (!$line) { quit("ERROR => HTTP read failed!", 1); }

        printmsg("DEBUG => getPage() received line: $line", 4);

        ## If it's a blank line we're done with the headers
        if ($line eq "\r\n") {
            $foundAllHeaders = 1;
        }

        ## Otherwise postpend the line to $headers
        else {
            $headers .= $line;

            ## Extract some important information
            if ($line =~ /Content-Length:\s*(\d+)/io) {
                $contentLength = $1;
                $contentRemaining = $1;
                printmsg("DEBUG => getPage() Content-Length: $contentLength", 1);
            }
            elsif ($line =~ /Transfer-Encoding:\s*(\S+)/io) {
                printmsg("DEBUG => getPage() Transfer-Encoding: $1", 1);
                $contentEncoding = $1;
            }
        }
    }

    ## If it's chunked data encoding
    if ($contentEncoding =~ /chunked/io) {
        ## Read lines
        my $cLength = -1;
        my $cRemaining = -1;
        while (my $line = getline($socket)) {

            ## Read the chunk header
            if ($cLength == -1) {
                $line =~ /^(\w+)/o;
                $cLength = $cRemaining = hex($1);
                printmsg("DEBUG => Receiving chunked message: $cLength bytes", 2);
            }

            ## Receive the body of the chunked message
            elsif ($cRemaining >= 1) {
                printmsg("DEBUG => getPage() read " .  length($line) . " of $cRemaining remaining bytes", 1);
                $cRemaining -= length($line);
                $data .= substr($line, 0, $cRemaining);
            }

            ## cRemaining will be -2 when we're done receiving a chunk
            ## so read any remaining chunk footers
            elsif ($cRemaining == -2) {
                ## If we're at the end of footers, set $cRemaining to -1 so it will start reading headers again.
                if ($line eq "\r\n") {
                    $cRemaining = -1;
                }
            }
        }
    }

    ## If it's normal data encoding
    else {
        ## Now read the rest of the data
        while ($contentRemaining > 0) {
            my $line = getline($socket);
            my $bytes = length($line);
            $data .= $line;
            $contentRemaining -= $bytes;
            printmsg("DEBUG => getPage() received $bytes of data: $line", 4);
        }

        ## Validate our data
        if (length($data) != $contentLength) {
            printmsg("NOTICE => getPage() Content-Length specified ($contentLength) and actual bytes read (" . length($data) . ") don't match!", 0);
        }
    }

    ## Return the data
    if ($return_headers == 1) {
        return($headers . "\r\n" . $data);
    }
    return($data);
}















#############################
##
##      MAIN PROGRAM
##
#############################

## Initialize signal handlers and such
initialize();

## Process Command Line
processCommandLine();

## Read the configuration file if there is one
if (-e $conf{'configurationFile'}) {
    readConfigurationFile($conf{'configurationFile'}, "networking", \%networking);
    readConfigurationFile($conf{'configurationFile'}, "logging", \%logging);
}


##
## Prepare to make a query to the web server
##

## Make an "options" string, of the options specified with -o KEY=VALUE KEY=VALUE ....
## that we can pass to the web server.
my $option_string = "";
$opt{'unix_username'} = $conf{'unix_username'};
foreach my $key (keys(%opt)) {
    ## If the value specified is a file, or -, load the file's contents (or STDIN) as the value
    if ( (-e $opt{$key} and -r $opt{$key}) or ($opt{$key} eq '-') ) {
        my $FILE;
        if(!open($FILE, ' ' . $opt{$key})) {
            quit("ERROR => couldn't open input file, $opt{$key}, $!", 1);
        }
        $opt{$key} = '';
        while (<$FILE>) {
            $opt{$key} .= $_;
        }
        ## Strip an ending \n (so that `echo lnx101 | dcm.pl -r test key=-` works properly)
        $opt{$key} =~ s/\r?\n$//os;
    }

    ## Quote any "special" characters in the value.
    ## Specifically the '=' and '&' characters need to be escaped.
    $opt{$key} =~ s/[=&]/\\$&/gos;

    ## Now add the key=value pair to the $option_string
    $option_string .= "$key=$opt{$key}&";
}
$option_string =~ s/\&$//o;
$option_string = urlencode($option_string);

## Connect to the web server, use a 10 second timeout for initial connect
my $SOCKET;
$SIG{'ALRM'} = sub { $conf{'error'} = "timeout"; };
alarm(10);
if ($networking{'useSSL'} == 1) {
    printmsg("DEBUG => Establishing HTTPS connection to: https://$networking{'webHost'}:$networking{'webPort'}/", 1);
    $SOCKET = new IO::Socket::SSL->new(PeerAddr  => $networking{'webHost'},
                                       PeerPort  => $networking{'webPort'},
                                       Proto     => 'tcp',
                                       Autoflush => 1,
                                       Blocking  => 1,
                                       timeout   => $networking{'timeout'},
    ) or quit("ERROR => SSL connection to $networking{'webHost'}:$networking{'webPort'} failed. Error was: $!",1);
}
else {
    printmsg("DEBUG => Establishing HTTP connection to: http://$networking{'webHost'}:$networking{'webPort'}/", 1);
    printmsg("WARNING => Connection insecure! Please install the Perl IO::Socket::SSL module.", 0, *STDERR);
    $SOCKET = IO::Socket::INET->new(      PeerAddr  => $networking{'webHost'},
                                          PeerPort  => $networking{'webPort'},
                                          Proto     => 'tcp',
                                          Autoflush => 1,
                                          Blocking  => 1,
                                          timeout   => $networking{'timeout'},
    ) or quit("ERROR => Connection to $networking{'webHost'}:$networking{'webPort'} failed. Error was: $!",1);
}
alarm(0);

## Download Page
my $response = getPage(
                   "http://$networking{'webHost'}/ona/modules/index.php" .
                   "?nohtml=1" .
                   "&debug=$debug" .
                   "&ona_context=" . $conf{'ona_context'} .
                   "&module=" . $self{'module'},

                   "options=$option_string",

                   0,

                   \$SOCKET
               );

## Disconnect
$SOCKET->close();

## Get the exit status of the function we ran -- it's the number in the first line of $result
## We default to 1 - so we'll exit with an error unless otherwise instructed.
my $status = 1;
if ($response =~ s/^(\d+)\r\n//s) {
    $status = $1;
}

## INTERRUPTION:  If the "mode" is to create symlinks, do that now.
if ($self{'module'} eq 'get_module_list' and $opt{'type'} eq 'perl') {
    my %modules = ();
    eval $response;
    if (!-f $conf{'programName'}) { quit("ERROR => Can't create symlink - source file $conf{'programName'} doesn't exist!", 1); }
    foreach my $module (keys(%modules)) {
        symlink($conf{'programName'}, $module);
    }
    quit("NOTICE => Symlinks for all modules created in current directory", 0);
}

## Print the result
print $response;

## Quit with no errors
quit("", $status);
