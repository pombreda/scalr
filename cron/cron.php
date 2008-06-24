<?
    declare(ticks = 1);
	define("NO_TEMPLATES", true);
	require_once(dirname(__FILE__)."/../src/prepend.inc.php");    

	CONTEXTS::$APPCONTEXT = APPCONTEXT::CRONJOB;
	
	Core::Load("IO/PCNTL/interface.IProcess.php");
	Core::Load("IO/PCNTL");
    Core::Load("System/Independent/Shell/ShellFactory");
    Core::Load("NET/SNMP");
    
    register_shutdown_function("shutdown");
    
    function shutdown()
    {
        @file_put_contents(dirname(__FILE__)."/cron.pid", "");
    }
    
    $fname = basename($argv[0]);

    $JobLauncher = new JobLauncher(dirname(__FILE__));
    
	$Shell = ShellFactory::GetShellInstance();
	    
    $pid = file_get_contents(dirname(__FILE__)."/cron.pid");
    if ($pid)
    {
        $ps = $Shell->QueryRaw("ps aux | grep '{$pid}' | grep 'cron' | grep '{$JobLauncher->GetProcessName()}'");    
        if ($ps)
            $Logger->info("'{$fname} --{$JobLauncher->GetProcessName()}' already running. Exiting.");
    }
    
    @file_put_contents(dirname(__FILE__)."/cron.pid", posix_getpid());
		
	
	
	$Logger->info(sprintf("Starting %s cronjob...", $JobLauncher->GetProcessName()));
	
	$JobLauncher->Launch(10);
?>

