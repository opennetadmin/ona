#!/usr/bin/perl -w
###############################################################################################
##  Albertson's Standardized Perl Library
##  
##  This file contains generic functions used by most/all perl scripts written for use by 
##  the Datacom group.  Functions that are not used by most/all of the scripts should be
##  broken out into separate files to make script start time as minimal as possible.
##  
##  CODING STANDARDS
##  Coding standards and styles for Perl are documented in the Datacom WIKI, currently
##  located here: https://wiki.datacom/dokuwiki/doku.php?id=codedev#perl
##  Synopsis:
##    * Comments are to begin with two pounds: ##
##    * Use an indent of 4 spaces, with NO TABS!
##    * Functions must have a descriptive header with the name, description, input 
##      requirements, output, and example usage.
##    * At least one command line argument is REQUIRED before a script does anything!
##    * When no command line arguments are supplied the script should display a usage summary.
##    * The "-h" and "--help" command line options must be supported.
##    * Scripts must exit with an error code of 0 on success, and non-zero on error.
##  
##############################################################################
##  
##  VERSION: 1.05
##  
##  CHANGELOG:
##      
##      01/03/2006 - v1.05 - Brandon Zehm
##          -  Updated printmsg - added a third "filehandle" argument
##          
##      11/15/2005 - v1.04 - Brandon Zehm
##          - Added function createPidFile()
##          - Added function checkPidFile()
##      
##  
###############################################################################################
use strict;
use IO::Socket;



##
## GLOBAL VARIABLES
## These global variables are availble in all scopes and should be
## used in all Datacom scripts where approperiate.
## The defaults set here are only used if they are not overridden
## from a configuration file or command line parameter.  I use "our"
## rather than "my" to declare these super-global variables that need
## to be accessed and modified everywhere.
##


## General configuration settings
our %conf = (
    
    ## Predefined
    'programName'          => $0,                              ## The name of this program
    'hostname'             => 'localhost',                     ## Used in printmsg() for all output.
    
    ## DEFINE THESE IN EACH SCRIPT AS NECESSARY
    'version'              => '0.00',                          ## The version of this program
    'authorName'           => 'Datacom',                       ## Author's Name
    'authorEmail'          => 'datacom@albertsons.com',        ## Author's Email Address
    'configurationFile'    => '',                              ## Default configuration file
    
    ## For printing colors to the console
    'colorBold'            => "\033[1m",
    'colorNoBold'          => "\033[0m",
    'colorWhite'           => "\033[37;1m",
    'colorNormal'          => "\033[m",
    'colorRed'             => "\033[31;1m",
    'colorGreen'           => "\033[32;1m",
    'colorCyan'            => "\033[36;1m",

);


## Logging options
our %logging = (
    
    ## OVERRIDE THESE IN EACH SCRIPT AS NECESSARY
    'stdout'                 => 1,                             ## Print messages to stdout  (0=no, 1=yes)
    'logFile'                => '',                            ## Log messages to specified file
    'logging'                => 0,                             ## Log messages - don't set manually! set logFile option. (0=no, 1=yes)
    'syslog'                 => 0,                             ## Syslog messages  (0=no, 1=yes)
    'facility'               => 1,                             ## Syslog faciltiy (1 == USER)
    'priority'               => 6,                             ## Syslog priority (6 == INFO)

);


## This is a global hash to make life easy.  Info about the 
## state of the program or temporary messages get stored in here.
our %self = (
    'error'                  => '',
);


## Debug/Verbosity level of the script.
## This is it's own scalar (rather than in a hash) to improve speed.
our $debug = 0;











##
## INITIALIZATION
## Here is a small bit of initialization code that gets run
## before everything else.
##

## Fixup $conf{'programName'}
$conf{'programName'} =~ s/(.)*[\/,\\]//;
$0 = $conf{'programName'} . " " . join(" ", @ARGV);

## Fixup $conf{'hostname'}
if ($conf{'hostname'} eq 'localhost') {
    $conf{'hostname'} = "";
    
    if ($ENV{'HOSTNAME'}) {
        $conf{'hostname'} = lc($ENV{'HOSTNAME'});
    }
    elsif ($ENV{'COMPUTERNAME'}) {
        $conf{'hostname'} = lc($ENV{'COMPUTERNAME'});
    }
    else {
        ## Try the hostname module
        eval { require Sys::Hostname; };
        unless ($@) {
            Sys::Hostname->import qw(hostname);
            $conf{'hostname'} = lc(hostname());
        }
    }
    
    ## Assign a name of "localhost" if it can't find anything else.
    if (!$conf{'hostname'}) {
        $conf{'hostname'} = 'localhost';
    }
    
    $conf{'hostname'} =~ s/\..*$//;  ## Remove domain name if it's present
}











###############################################################################################
##  Function:    printmsg (string $message, [int $level], [*FILEHANDLE])
##
##  Description: Handles all messages - 
##               Depending on the state of the program it will log
##               messages to a log file, print them to STDOUT or both.
##               
##
##  Input:       $message          A message to be printed, logged, etc.
##               $level            The debug level of the message. If not defined 0
##                                 will be assumed.  0 is considered a normal message, 
##                                 1 and higher is considered a debug message.
##               $filehandle       Optional reference to a filehandle to print to rather than
##                                 printing to STDOUT.
##  
##  Output:      Prints to STDOUT, to $logging{'LOGHANDLE'}, both, or none depending 
##               on the state of the program and the debug level specified.
##  
##  Example:     printmsg("ERROR => The file could not be opened!", 0);
###############################################################################################
sub printmsg {
    ## Assign incoming parameters to variables
    my ( $message, $level, $fh ) = @_;
    
    ## Make sure input is sane
    $level = 0 if (!defined($level));
    
    ## Continue only if the debug level of the program is >= message debug level.
    if ($debug >= $level) {
        
        ## Use STDOUT if a filehandle wan't specified
        if (!$fh) {
            $fh = *STDOUT;
        }
        
        ## Change \r\n's to spaces
        $message =~ s/\r?\n/ /go;
        
        ## Get the date in the format: Dec  3 11:14:04
        my ($sec, $min, $hour, $mday, $mon) = localtime();
        $mon = ('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec')[$mon];
        my $date = sprintf("%s %02d %02d:%02d:%02d", $mon, $mday, $hour, $min, $sec);
        
        ## Syslog the message is needed
        if ($logging{'syslog'}) {
            syslog('/dev/log', $logging{'priority'}, $logging{'facility'}, "$conf{'programName'}\[$$\]: $message");
        }
        
        ## Print to STDOUT always if debugging is enabled, or if the mode is NOT "running".
        if ($logging{'stdout'}) {
            print {$fh} "$date $conf{'hostname'} $conf{'programName'}\[$$\]: $message\n";
        }
        
        ## Print to the log file if $logging{'logging'} is true
        if ($logging{'logging'}) {
            print {$logging{'LOGHANDLE'}} "$date $conf{'hostname'} $conf{'programName'}\[$$\]: $message\n";
        }
        
    }
    
    ## Return 0 errors
    return(0);
}














###############################################################################################
## FUNCTION:    
##   openLogFile ( $filename )
## 
## 
## DESCRIPTION: 
##   Opens the file $filename and attaches it to the filehandle "$logging{'LOGHANDLE'}".  Returns 0 on success
##   and non-zero on failure.  Error codes are listed below, and the error message gets set in
##   global variable $!.
##   
##   
## Example: 
##   openFile ("/var/log/scanAlert.log");
##
###############################################################################################
sub openLogFile {
    ## Get the incoming filename
    my $filename = $_[0];
    
    ## Make sure our file exists, and if the file doesn't exist then create it
    if ( ! -f $filename ) {
        printmsg("NOTICE => The file [$filename] does not exist.  Creating it now with mode [0600].", 0);
        open ($logging{'LOGHANDLE'}, ">>$filename");
        close $logging{'LOGHANDLE'};
        chmod (0600, $filename);
    }
    
    ## Now open the file and attach it to a filehandle
    open ($logging{'LOGHANDLE'},">>$filename") or return (1);
    
    ## Put the file into non-buffering mode
    select $logging{'LOGHANDLE'};
    $| = 1;
    select STDOUT;
    
    ## Tell the rest of the program that we can log now
    $logging{'logging'} = 1;
    
    ## Return success
    return(0);
}

















###############################################################################################
## FUNCTION:
##   createPidFile ($pidfilename)
##
##
## DESCRIPTION:
##   Opens the file $pidfilename and writes the pid of the current process in it.
##   Returns 0 on success, non-zero on error.  On error an error message is stored
##   in the global var $self{'error'}.
##
## Example:
##   createPidFile ("/var/run/bla.pid");
##
###############################################################################################
sub createPidFile {
    ## Get the pidfile name
    my $pidfilename = $_[0];
    my $PIDFILE;
    
    ## If $pidfilename is empty then we have been invoked incorrectly - return an error.
    if (!$pidfilename) {
        $self{'error'} = "ERROR => createPidFile() required parameter missing.";
        return(1);
    }
    
    ## Open the PID file
    if (! open ($PIDFILE, "> $pidfilename")) { $self{'error'} = $!; return 1; }
    
    ## Put the pid in the file
    print $PIDFILE "$$\n";
    close $PIDFILE;
    if (! chmod (0600, $pidfilename)) { $self{'error'} = $!; return 2; }
    
    ## Return success (0 errors)
    return(0);
}












###############################################################################################
## FUNCTION:
##   checkPidFile ($pidfilename, $process_name)
##
##
## DESCRIPTION:
##   Returns an error (a number >= 1) if the pid file exists, and the pid specified in it
##   references a currently running process named $process_name.  On error an error message is
##   stored in the global var $self{'error'}.
##   Returns 0 on succcess
##
## Example:
##   checkPidFile("/var/run/bla.pid", "dns_vip.pl");
##
###############################################################################################
sub checkPidFile {
    ## Get the pidfile name
    my ($pidfilename, $psname) = @_;
    my $PIDFILE;
    
    printmsg("DEBUG => checkPidFile() checking to see if it's safe for us to continue running.", 2);
    
    ## If $pidfilename is empty then the user has not specified a PID file and it is
    ## safe for us to return 0 and continue running.
    if (!$pidfilename) {
        printmsg("DEBUG => checkPidFile() returning 0 since the pid file was empty (not specified.)", 3);
        return(0);
    }
    
    ## Check to see if the file exists
    if (! -e $pidfilename) {
        ## Return success if the PID file doesn't exist.
        printmsg("DEBUG => checkPidFile() returning 0 since the pid file specified doesn't exist.", 3);
        return(0);
    }
    
    ## Open the PID file.
    if (! open($PIDFILE, $pidfilename)) {
        ## Return an error if it exists but we couldn't open it.
        $self{'error'} = "ERROR => Couldn't open the PID file: $pidfilename";
        return(1);
    }
    
    ## Read the PID from the file into $pid
    my $pid = <$PIDFILE>;
    chomp $pid;
    close $PIDFILE;
    
    ## If the PID file contains our own PID it's safe to continue running.
    if ($pid == $$) {
        printmsg("DEBUG => checkPidFile() returning 0 since the PID file contains my PID.", 3);
        return(0);
    }
    
    ## Check to see if that process is running
    ## FIXME: I don't think using /proc/ is compatible throughout various unices. - Kurt Keller
    if (! -d "/proc/$pid") {
        ## Return success if the PID is not running
        printmsg("DEBUG => checkPidFile() returning 0 since the PID specified in the pid file is not running.", 3);
        return(0);
    }
    
    ## Open the PID cmdline file so we can get the process name for the running PID.
    if (! open($PIDFILE, "/proc/$pid/cmdline")) {
        ## Return an error if it exists but we couldn't open it.
        $self{'error'} = "ERROR => opening proc file /proc/$pid/cmdline returned the error: $!";
        return(2);
    }
    
    ## proc/xxx/cmdline can be split by \0 to get the different parts
    my $pidname = <$PIDFILE>;
    $pidname =~ s/\0/ /;
    
    ## If $psname (from input) is found in the line from cmdline, the process matches so we retun an error
    if (index($pidname,$psname) >= 0) {
        $self{'error'} = "ERROR => the process referenced in the pid file [$pidfilename] is another [$psname] process";
        return(3);
    }
    
    ## If we get to here it's safe for us to start
    printmsg("DEBUG => checkPidFile() returning 0 since the process referenced in the pid file [$pidfilename] is not another [$psname] process.", 3);
    return(0);
}











###############################################################################################
## FUNCTION:    
##   syslog (string $socketName, int $priority, int $facility, string $message)
## 
## 
## DESCRIPTION: 
##   Connects to the socket $socketName, and sends it a syslog formatted message.  
##   i.e. it syslog's $message with the priority and facility specified.
##   Returns 0 on success, non-zero on error.  If an error occurs the error message
##   is stored in the global variable $self{'error'}.
##
## Example: 
##   syslog("/dev/log", 6, 1, "Test syslog message");
##
##
## Priorities (on a Linux system)
##   LOG_EMERG       0       system is unusable
##   LOG_ALERT       1       action must be taken immediately
##   LOG_CRIT        2       critical conditions
##   LOG_ERR         3       error conditions
##   LOG_WARNING     4       warning conditions
##   LOG_NOTICE      5       normal but significant condition
##   LOG_INFO        6       informational
##   LOG_DEBUG       7       debug-level messages
##
##
## Facilities (on a Linux system)
##   LOG_KERN        0       kernel messages
##   LOG_USER        1       random user-level messages
##   LOG_MAIL        2       mail system
##   LOG_DAEMON      3       system daemons
##   LOG_AUTH        4       security/authorization messages
##   LOG_SYSLOG      5       messages generated internally by syslogd
##   LOG_LPR         6       line printer subsystem
##   LOG_NEWS        7       network news subsystem
##   LOG_UUCP        8       UUCP subsystem
##   LOG_CRON        9       clock daemon
##   LOG_AUTHPRIV    10      security/authorization messages (private)
##   LOG_FTP         11      ftp daemon
##   LOG_LOCAL0      16      reserved for local use
##   LOG_LOCAL1      17      reserved for local use
##   LOG_LOCAL2      18      reserved for local use
##   LOG_LOCAL3      19      reserved for local use
##   LOG_LOCAL4      20      reserved for local use
##   LOG_LOCAL5      21      reserved for local use
##   LOG_LOCAL6      22      reserved for local use
##   LOG_LOCAL7      23      reserved for local use
##
## v1.1 - 01/16/2004 - Textual changes to error messages
## v1.2 - 03/27/2004 - Remove hashes to improve performance
###############################################################################################
sub syslog {
    
    ## Get incoming variables
    my ($socketName, $priority, $facility, $message) = @_;
    
    ## Set defaults if some variables are not defined
    $socketName  = "/dev/log"         if (!defined($socketName));
    $priority    = 6                  if (!defined($priority));
    $facility    = 1                  if (!defined($facility));
    $message     = "Default Message"  if (!defined($message));
    
    ## Bit-shift the facility (black magic)
    $facility = ($facility << 3);
    
    ## Make sure values are sane (more black magic)
    $priority = $priority & 0x07;
    $facility = $facility & 0x3f8;
    
    ## Generate syslog value for the priority and facility
    my $value = ($priority + $facility);
    
    if (! -S $socketName) {
        $self{'error'} = "ERROR => The specified syslog socket [$socketName] either does not exist or is not a socket.";
        return(1);
    }
    
    ## Open a UNIX socket in dgram mode with udp protocol. 
    socket(SOCKET, PF_UNIX, SOCK_DGRAM, 0) or do { 
        $self{'error'} = "ERROR => syslog(): $!";
        return(2);
    };
    
    ## Connect our socket to SOCKET
    connect(SOCKET, sockaddr_un ($socketName)) or do {
        $self{'error'} = "ERROR => syslog(): $!";
        return(3);
    };
    
    ## Sending the message to the syslog socket
    print SOCKET "<$value>$message" or do {
        $self{'error'} = "ERROR => syslog(): $!";
        return(4);
    };
    
    ## Close the socket
    close SOCKET;
    
    ## Return success
    return(0);
}












###############################################################################################
## FUNCTION:    
##   returnConfigurationFileSections ( string $filename )
## 
## 
## DESCRIPTION: 
##   Reads the file $filename, finds any defined sections, and returns a list of them.  
##   This version of this function reads .ini style files.
##
## Example: 
##   my @list = returnConfigurationFileSections("/etc/configuration.conf");
##
###############################################################################################
sub returnConfigurationFileSections {
    
    ## Get incoming variables
    my ($fileName) = @_;
    
    ## Verify input
    if ( (!$fileName) ) {
        $self{'error'} = "ERROR => returnConfigurationFileSections() One or more incoming parameters were empty";
        return(1);
    }
    
    ## Open the file
    open(CONFFILE, $fileName) or quit("ERROR => Error while opening the configuration file [$fileName].  The error was [$!]",1);
    
    ## Read entire file into a scalar so we can effectivly remove c style /* */ comments
    my $file = "";
    while (<CONFFILE>) {
        $file .= $_;
    }
    close(CONFFILE);
    
    ## Remove comments and whitespace
    $file =~ s/\/\*.*?\*\///ogs;        ## Remove C Style /* */ style comments
    $file =~ s/([^\\])\#.*$/$1/ogm;     ## Remove pound (#) comments - unless backslash escaped
    $file =~ s/\\\#/\#/ogm;             ## Replace '\#' with '#' 
    $file =~ s/\/\/.*$//ogm;            ## Remove slash (//) comments 
    $file =~ s/^\s*(\n|\r\n)//ogm;      ## Remove blank lines
    
    ## Find section names and store them into an array
    my @entries = ();
    foreach my $line (split(/\r?\n/, $file)) {
        if ($line =~ /^\s*\[(.*)\]\s*$/) {
            push(@entries, $1);
        }
    }
    
    ## Check for duplicate entries
    foreach my $entry (@entries) {
        if (scalar(grep(/^$entry$/, @entries)) >= 2) {
            printmsg("WARNING => There are two section definitions in the configuration file [$file] named [$entry]", 0);
        }
    }
    
    ## Return
    return(@entries);
}


















###############################################################################################
## FUNCTION:    
##   readConfigurationFile ( string $filename, string $section, reference $hash_reference )
## 
## 
## DESCRIPTION: 
##   Reads the file $filename, and stores information from the file into the hash 
##   at $hash_reference.  This version of this function reads .ini style files.
##
## Example: 
##   readConfigurationFile("/etc/configuration.conf", "general", \%myHash);
##
###############################################################################################
sub readConfigurationFile {
    
    ## Get incoming variables
    my ($fileName, $section, $hashref) = @_;
    
    
    ## Verify input
    if ( (!$fileName) or (!$section) or (!$hashref) ) {
        printmsg("DEBUG => readConfigurationFile() Incoming parameters are: file: $fileName,  section: $section, hashref: $hashref", 2);
        $self{'error'} = "ERROR => readConfigurationFile() One or more incoming parameters were empty";
        return(1);
    }
    
    ## Open the file
    open(CONFFILE, $fileName) or quit("ERROR => Opening the configuration file [$fileName] returned the error [$!]",1);
    
    ## Read the file into a single scalar variable
    my $file = "";
    while (<CONFFILE>) { $file .= $_; }
    close(CONFFILE);
    
    ## Remove comments and whitespace
    $file =~ s/\/\*.*?\*\///ogs;      ## Remove C Style /* */ style comments
    $file =~ s/([^\\])\#.*$/$1/ogm;   ## Remove pound (#) comments - unless backslash escaped
    $file =~ s/\\\#/\#/ogm;           ## Replace '\#' with '#' 
    $file =~ s/\/\/.*$//ogm;          ## Remove slash (//) comments 
    $file =~ s/^\s*(\n|\r\n)//ogm;    ## Remove blank lines
    
    
    ## Find the section we want and store the lines of that section in @lines
    my $inSection = 0;
    foreach my $line (split(/\r?\n/, $file)) {
        if ($inSection == 1) {
            if ($line =~ /^\s*\[.*\]\s*$/) {
                ## We're at the end of the section (starting a new section), stop
                $inSection = 0;
                last;
            }
            else {
                ## This is a normal line in the section, parse it and add data to $hashref
                if ($line) {
                    ## Remove whitespace
                    $line =~ s/(^\s+)|(\s+$)//og;
                    ## Don't continue unless the line looks valid
                    next if ( (!$line) or ($line !~ /=>/) );
                    ## Get and store the key/value pair
                    my ($key, $value) = split(/\s*=>\s*/, $line);
                    printmsg("DEBUG => readConfigurationFile() [$section] Found key value pair: $key => $value", 3);
                    ${$hashref}{$key} = $value;
                }
            }
        }
        ## If we found the right section, start recording it's lines to @lines
        if ($line =~ /^\s*\[$section\]\s*$/) {
            $inSection = 1;
        }
    }
    
    ## Return 0 errors
    return(0);
}














######################################################################
##  Function:    quit (string $message, int $errorLevel)
##  
##  Description: Exits the program, optionally printing $message.  It 
##               returns an exit error level of $errorLevel to the 
##               system  (0 means no errors, and is assumed if empty.)
##
##  Example:     quit("Exiting program normally", 0);
######################################################################
sub quit {
    my ( $message, $errorLevel ) = @_;
    $errorLevel = 0 if (!defined($errorLevel));
    
    ## Print exit message
    if ($message) { 
        ## Change the syslog facility to 3/daemon if there is an exit error.
        if ($errorLevel >= 0) {
            $logging{'facility'} = 3;
        }
        printmsg($message, 0);
    }
    
    ## Exit
    exit($errorLevel);
}











######################################################################
##           Albertsons Legacy Variables and Functions              ##
######################################################################


sub ABEND {
    printmsg("DEBUG => The ABEND() function is depricated, please use quit() instead.", 1);
    quit($_[0], 1);
}

#sub READ_PARM {
#    printmsg("DEBUG => The READ_PARM() function is depricated!", 1);
#    foreach $_ (@_) {
#        $Result=`PARM=$Prgm /alb/bin/readparm $_`;
#        eval { $$_ = $Result; };
#    }
#}

sub WRITE_LOG {
    printmsg("DEBUG => The WRITE_LOG() function is depricated, please use printmsg() instead.", 1);
    openLogFile('/alb/log/' . $conf{'programName'} . '.log') or die $!;
    printmsg($_[0], 0);
    close $logging{'LOGHANDLE'};
}

# sub PROMPT {
#     printmsg("DEBUG => The PROMPT() function is depricated, please use <> instead.", 1);
#     print $_[0] if $_[0];
#     return(<>);
# }

# sub ERROR {
#     printmsg("DEBUG => The ERROR() function is depricated, please use printmsg() instead.", 1);
#     print 'ERROR => ' . $_[0] . "\n";
#     print 'Press ENTER to continue...';
#     <>;
# }

# sub BECHO {
#     printmsg("DEBUG => The BECHO() function is depricated, please use printmsg() instead.", 1);
#     print $conf{'colorBold'} . $_[0] . $conf{'colorNoBold'};
# }

# sub PRESS_ENTER{
#     printmsg("DEBUG => The PRESS_ENTER() function is depricated, please use <> instead.", 1);
#     <>;
# }





## End with a 1, so that a require() call succeeds.
1;