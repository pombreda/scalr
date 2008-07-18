<?
    Core::Load("Data/RRD");
    
    class CPUSNMPWatcher
    {
        /**
	     * Watcher Name
	     *
	     * @var string
	     */
	    public $WatcherName = "CPU Usage (SNMP)";
		
		const COLOR_CPU_USER = "#eacc00";
		const COLOR_CPU_SYST = "#ea8f00";
		const COLOR_CPU_NICE = "#ff3932";
		const COLOR_CPU_IDLE = "#fafdce";
		
		private $RRD;
		
		/**
		 * Constructor
		 *
		 */
    	function __construct($SNMPTree, $path)
		{
		      $this->Path = $path;
		      $this->SNMPTree = $SNMPTree;
		}
        
        /**
         * This method is called after watcher assigned to node
         *
         */
        public function CreateDatabase($name)
        {            
            @mkdir($this->Path."/CPUSNMP/{$name}", 0777, true);
            
            $this->RRD = new RRD($this->Path."/CPUSNMP/{$name}/cpu.rrd");
            
            $this->RRD->AddDS(new RRDDS("user", "COUNTER", 180));
            $this->RRD->AddDS(new RRDDS("system", "COUNTER", 180));
            $this->RRD->AddDS(new RRDDS("nice", "COUNTER", 180));
            $this->RRD->AddDS(new RRDDS("idle", "COUNTER", 180));
            
            $this->RRD->AddRRA(new RRA("AVERAGE", array(0.5, 1, 800)));
            $this->RRD->AddRRA(new RRA("AVERAGE", array(0.5, 6, 800)));
            $this->RRD->AddRRA(new RRA("AVERAGE", array(0.5, 24, 800)));
            $this->RRD->AddRRA(new RRA("AVERAGE", array(0.5, 288, 800)));

            $this->RRD->AddRRA(new RRA("MAX", array(0.5, 1, 800)));
            $this->RRD->AddRRA(new RRA("MAX", array(0.5, 6, 800)));
            $this->RRD->AddRRA(new RRA("MAX", array(0.5, 24, 800)));
            $this->RRD->AddRRA(new RRA("MAX", array(0.5, 288, 800)));
            
            $this->RRD->AddRRA(new RRA("LAST", array(0.5, 1, 800)));
            $this->RRD->AddRRA(new RRA("LAST", array(0.5, 6, 800)));
            $this->RRD->AddRRA(new RRA("LAST", array(0.5, 24, 800)));
            $this->RRD->AddRRA(new RRA("LAST", array(0.5, 288, 800)));
            
            $res = $this->RRD->Create("-1m", 60);
            
            @chmod($this->Path."/CPUSNMP/{$name}/cpu.rrd", 0777);
            
            return $res;
        }
               
        /**
         * Retreive data from node
         *
         */
        public function RetreiveData($name)
        {
            //
            // Add data to rrd
            //    
            preg_match_all("/[0-9]+/si", $this->SNMPTree->Get(".1.3.6.1.4.1.2021.11.50.0"), $matches);
            $CPURawUser = $matches[0][1];
                        
            preg_match_all("/[0-9]+/si", $this->SNMPTree->Get(".1.3.6.1.4.1.2021.11.52.0"), $matches);
            $CPURawSystem = $matches[0][1];
            
            preg_match_all("/[0-9]+/si", $this->SNMPTree->Get(".1.3.6.1.4.1.2021.11.53.0"), $matches);
            $CPURawIdle = $matches[0][1];
            
            preg_match_all("/[0-9]+/si", $this->SNMPTree->Get(".1.3.6.1.4.1.2021.11.51.0"), $matches);
            $CPURawNice = $matches[0][1];
			
            return array($CPURawUser, $CPURawSystem, $CPURawNice, $CPURawIdle);
        }
        
    	public function UpdateRRDDatabase($name, $data)
        {
        	if (!file_exists("{$this->Path}/CPUSNMP/{$name}/cpu.rrd"))
        		$this->CreateDatabase($name);
        	
        	if (!$this->RRD)
                $this->RRD = new RRD($this->Path."/CPUSNMP/{$name}/cpu.rrd");
  
            $data = array_map("ceil", $data);
                
        	$this->RRD->Update($data);
        }
        
        /**
         * Plot graphic
         *
         * @param integer $serverid
         */
        public function PlotGraphic($name)
        {
        	$image_path = "{$this->Path}/graphics/{$name}/cpu.gif";
        	
        	if (file_exists($image_path))
        	{
        		clearstatcache();
        		$time = filemtime($image_path);
        		
        		if ($time > time()-300)
        			return false;
        	}
        	else
        		@mkdir(dirname($image_path), 0777, true);

        		
        	$graph = new RRDGraph(440, 160, CONFIG::$RRDTOOL_PATH);
			$graph->AddDEF("a", $this->Path."/CPUSNMP/{$name}/cpu.rrd", "user", "AVERAGE");
			$graph->AddDEF("b", $this->Path."/CPUSNMP/{$name}/cpu.rrd", "system", "AVERAGE");
			$graph->AddDEF("c", $this->Path."/CPUSNMP/{$name}/cpu.rrd", "nice", "AVERAGE");
			$graph->AddDEF("d", $this->Path."/CPUSNMP/{$name}/cpu.rrd", "idle", "AVERAGE");

			        				
            $graph->AddCDEF("total", "a,b,c,d,+,+,+");
            
            $graph->AddCDEF("a_perc", "a,total,/,100,*");
            $graph->AddVDEF("a_perc_last", "a_perc,LAST");
            $graph->AddVDEF("a_perc_avg", "a_perc,AVERAGE");
            $graph->AddVDEF("a_perc_max", "a_perc,MAXIMUM");
            
            $graph->AddCDEF("b_perc", "b,total,/,100,*");
            $graph->AddVDEF("b_perc_last", "b_perc,LAST");
            $graph->AddVDEF("b_perc_avg", "b_perc,AVERAGE");
            $graph->AddVDEF("b_perc_max", "b_perc,MAXIMUM");
            
            $graph->AddCDEF("c_perc", "c,total,/,100,*");
            $graph->AddVDEF("c_perc_last", "c_perc,LAST");
            $graph->AddVDEF("c_perc_avg", "c_perc,AVERAGE");
            $graph->AddVDEF("c_perc_max", "c_perc,MAXIMUM");
            
            $graph->AddCDEF("d_perc", "d,total,/,100,*");
            $graph->AddVDEF("d_perc_last", "d_perc,LAST");
            $graph->AddVDEF("d_perc_avg", "d_perc,AVERAGE");
            $graph->AddVDEF("d_perc_max", "d_perc,MAXIMUM");
			
            $graph->AddComment('<b><tt>               Current    Average    Maximum</tt></b>\\j');
            
            $graph->AddArea("a_perc", self::COLOR_CPU_USER, "<tt>user    </tt>");
            $graph->AddGPrint("a_perc_last", '<tt>    %3.0lf%%</tt>');
            $graph->AddGPrint("a_perc_avg",  '<tt>     %3.0lf%%</tt>');
            $graph->AddGPrint("a_perc_max",  '<tt>     %3.0lf%%</tt>\\n');
            
            $graph->AddArea("b_perc", self::COLOR_CPU_SYST, "<tt>system  </tt>", "STACK");
            $graph->AddGPrint("b_perc_last", '<tt>    %3.0lf%%</tt>');
            $graph->AddGPrint("b_perc_avg",  '<tt>     %3.0lf%%</tt>');
            $graph->AddGPrint("b_perc_max",  '<tt>     %3.0lf%%</tt>\\n');
            
            $graph->AddArea("c_perc", self::COLOR_CPU_NICE, "<tt>nice    </tt>", "STACK");
            $graph->AddGPrint("c_perc_last", '<tt>    %3.0lf%%</tt>');
            $graph->AddGPrint("c_perc_avg",  '<tt>     %3.0lf%%</tt>');
            $graph->AddGPrint("c_perc_max",  '<tt>     %3.0lf%%</tt>\\n');
            
            $graph->AddArea("d_perc", self::COLOR_CPU_IDLE, "<tt>idle    </tt>", "STACK");
            $graph->AddGPrint("d_perc_last", '<tt>    %3.0lf%%</tt>');
            $graph->AddGPrint("d_perc_avg",  '<tt>     %3.0lf%%</tt>');
            $graph->AddGPrint("d_perc_max",  '<tt>     %3.0lf%%</tt>\\n');
            
            if (CONFIG::$RRD_DEFAULT_FONT_PATH)
            	$graph->AddFont("DEFAULT", "0", CONFIG::$RRD_DEFAULT_FONT_PATH);
            
            $graph->Plot($image_path, "-86400", false, 
                            array(
                            		"--pango-markup",
                            		"-v", "Percent CPU Utilization", 
                                    "-t", "CPU Utilization",
                                    "-u", "100", 
                                    "--alt-autoscale-max",
                            		"--alt-autoscale-min",
                                    "--rigid",
                            		"--no-gridfit",
                            		"--slope-mode",
                            		"--x-grid", "HOUR:1:HOUR:2:HOUR:2:0:%H"
                                 )
                         );
         
             return true;
        }
    }
?>