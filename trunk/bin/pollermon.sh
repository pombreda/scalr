
# resolve links - $0 may be a link to activemq's home
PRG="$0"
progname=`basename "$0"`
saveddir=`pwd`

# need this for relative symlinks
dirname_prg=`dirname "$PRG"`
cd "$dirname_prg"

while [ -h "$PRG" ] ; do
    ls=`ls -ld "$PRG"`
    link=`expr "$ls" : '.*-> \(.*\)$'`
    if expr "$link" : '.*/.*' > /dev/null; then
    PRG="$link"
    else
    PRG=`dirname "$PRG"`"/$link"
    fi
done


POLLERMON_HOME=`dirname "$PRG"`/..
cd "$saveddir"

# make it fully qualified
export POLLERMON_HOME=`cd "$POLLERMON_HOME" && pwd`

java -jar $POLLERMON_HOME/bin/pollermon.jar