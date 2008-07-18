<?
    Core::Load("Data/RRD");
    
    class LASNMPWatcher
    {
        /**
	     * Watcher Name
	     *
	     * @var string
	     */
	    public $WatcherName = "Load averages (SNMP)";
		
		private $RRD;
		
		
		const COLOR_LA1 = "#FF0000";
		const COLOR_LA5 = "#0000FF";
		const COLOR_LA15 = "#00FF00";
		
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
            @mkdir($this->Path."/LASNMP/{$name}", 0777, true);
            
            $this->RRD = new RRD($this->Path."/LASNMP/{$name}/la.rrd");
            
            $this->RRD->AddDS(new RRDDS("la1", "GAUGE", 180));
            $this->RRD->AddDS(new RRDDS("la5", "GAUGE", 180));
            $this->RRD->AddDS(new RRDDS("la15", "GAUGE", 180));
            
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
            
            @chmod($this->Path."/LASNMP/{$name}/la.rrd", 0777);
            
            return $res;
        }
               
        /**
         * Retreive data from node
         *
         */
        public function RetreiveData($name)
        {
            preg_match_all("/[0-9\.]+/si", $this->SNMPTree->Get(".1.3.6.1.4.1.2021.10.1.3.1"), $matches);
            $La1 = $matches[0][0];
            
            preg_match_all("/[0-9\.]+/si", $this->SNMPTree->Get(".1.3.6.1.4.1.2021.10.1.3.2"), $matches);
            $La5 = $matches[0][0];
            
            preg_match_all("/[0-9\.]+/si", $this->SNMPTree->Get(".1.3.6.1.4.1.2021.10.1.3.3"), $matches);
            $La15 = $matches[0][0];
            
            return array($La1, $La5, $La15);
        }
        
    	public function UpdateRRDDatabase($name, $data)
        {
        	if (!file_exists("{$this->Path}/LASNMP/{$name}/la.rrd"))
        		$this->CreateDatabase($name);
        	
        	if (!$this->RRD)
                $this->RRD = new RRD($this->Path."/LASNMP/{$name}/la.rrd");
  
        	$this->RRD->Update($data);
        }
        
        /**
         * Plot graphic
         *
         * @param integer $serverid
         */
        public function PlotGraphic($name)
        {
        	$image_path = "{$this->Path}/graphics/{$name}/la.gif";
        	
        	if (file_exists($image_path))
        	{
        		clearstatcache();
        		$time = filemtime($image_path);
        		
        		if ($time > time()-300)
        			return false;
        	}
        	else
        		@mkdir(dirname($image_path), 0777, true);

        		
        	$graph = new RRDGraph(440, 140, CONFIG::$RRDTOOL_PATH);
			$graph->AddDEF("la1", $this->Path."/LASNMP/{$name}/la.rrd", "la1", "AVERAGE");
			
			$graph->AddVDEF("la1_min", "la1,MINIMUM");
            $graph->AddVDEF("la1_last", "la1,LAST");
            $graph->AddVDEF("la1_avg", "la1,AVERAGE");
            $graph->AddVDEF("la1_max", "la1,MAXIMUM");
			
			$graph->AddDEF("la5", $this->Path."/LASNMP/{$name}/la.rrd", "la5", "AVERAGE");
			
			$graph->AddVDEF("la5_min", "la5,MINIMUM");
            $graph->AddVDEF("la5_last", "la5,LAST");
            $graph->AddVDEF("la5_avg", "la5,AVERAGE");
            $graph->AddVDEF("la5_max", "la5,MAXIMUM");
			
			$graph->AddDEF("la15", $this->Path."/LASNMP/{$name}/la.rrd", "la15", "AVERAGE");
			
			$graph->AddVDEF("la15_min", "la15,MINIMUM");
            $graph->AddVDEF("la15_last", "la15,LAST");
            $graph->AddVDEF("la15_avg", "la15,AVERAGE");
            $graph->AddVDEF("la15_max", "la15,MAXIMUM");
			
            $graph->AddComment('<b><tt>                              Minimum     Current     Average     Maximum</tt></b>\\j');
            
            $graph->AddArea("la15", self::COLOR_LA15, "<tt>15 Minutes system load:</tt>");
            $graph->AddGPrint("la15_min",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la15_last", '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la15_avg",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la15_max",  '<tt>%3.2lf</tt>\\j');
			
            $graph->AddLine(1, "la5", self::COLOR_LA5, "<tt> 5 Minutes system load:</tt>");
            $graph->AddGPrint("la5_min",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la5_last", '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la5_avg",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la5_max",  '<tt>%3.2lf</tt>\\j');
            
			$graph->AddLine(1, "la1", self::COLOR_LA1, "<tt> 1 Minute  system load:</tt>");
            $graph->AddGPrint("la1_min",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la1_last", '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la1_avg",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la1_max",  '<tt>%3.2lf</tt>\\j');          

            if (CONFIG::$RRD_DEFAULT_FONT_PATH)
            	$graph->AddFont("DEFAULT", "0", CONFIG::$RRD_DEFAULT_FONT_PATH);
            
            $graph->Plot($image_path, "-86400", false,
                            array(
                            		"--pango-markup",
                            		"-v", "Load averages", 
                                    "-t", "Load averages",
                                    "-l", "0",
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