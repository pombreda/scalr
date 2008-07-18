<?
    Core::Load("Data/RRD");
    
    class NETSNMPWatcher
    {
        /**
	     * Watcher Name
	     *
	     * @var string
	     */
	    public $WatcherName = "NET Usage (SNMP)";
		
		const COLOR_INBOUND = "#00cc00";
		const COLOR_OUBOUND = "#0000ff";
				
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
            @mkdir($this->Path."/NETSNMP/{$name}", 0777, true);
            
            $this->RRD = new RRD($this->Path."/NETSNMP/{$name}/net.rrd");
            
            $this->RRD->AddDS(new RRDDS("in", "COUNTER", 600));
            $this->RRD->AddDS(new RRDDS("out", "COUNTER", 600));
            
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
            
            @chmod($this->Path."/NETSNMP/{$name}/net.rrd", 0777);
            
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
            preg_match_all("/[0-9]+/si", $this->SNMPTree->Get(".1.3.6.1.2.1.2.2.1.10.2"), $matches);
            $in = $matches[0][1];
                        
            preg_match_all("/[0-9]+/si", $this->SNMPTree->Get(".1.3.6.1.2.1.2.2.1.16.2"), $matches);
            $out = $matches[0][1];

            return array($in, $out);
        }
        
    	public function UpdateRRDDatabase($name, $data)
        {
        	if (!file_exists("{$this->Path}/NETSNMP/{$name}/net.rrd"))
        		$this->CreateDatabase($name);
        	
        	if (!$this->RRD)
                $this->RRD = new RRD($this->Path."/NETSNMP/{$name}/net.rrd");
  
            $this->RRD->Update($data);
        }
        
        /**
         * Plot graphic
         *
         * @param integer $serverid
         */
        public function PlotGraphic($name)
        {
        	$image_path = "{$this->Path}/graphics/{$name}/net.gif";
        	
        	if (file_exists($image_path))
        	{
        		clearstatcache();
        		$time = filemtime($image_path);
        		
        		if ($time > time()-300)
        			return false;
        	}
        	else
        		@mkdir(dirname($image_path), 0777, true);

        		
        	$graph = new RRDGraph(440, 100, CONFIG::$RRDTOOL_PATH);
			$graph->AddDEF("in", $this->Path."/NETSNMP/{$name}/net.rrd", "in", "AVERAGE");
			$graph->AddDEF("out", $this->Path."/NETSNMP/{$name}/net.rrd", "out", "AVERAGE");
			
			$graph->AddCDEF("in_bits", "in,8,*");
			$graph->AddCDEF("out_bits", "out,8,*");
			
			$graph->AddVDEF("in_last", "in_bits,LAST");
            $graph->AddVDEF("in_avg", "in_bits,AVERAGE");
            $graph->AddVDEF("in_max", "in_bits,MAXIMUM");
            
            $graph->AddVDEF("out_last", "out_bits,LAST");
            $graph->AddVDEF("out_avg", "out_bits,AVERAGE");
            $graph->AddVDEF("out_max", "out_bits,MAXIMUM");
            
            $graph->AddComment('<b><tt>           Current   Average   Maximum</tt></b>\\j');
            
			$graph->AddArea("in_bits", self::COLOR_INBOUND, "<tt>In:    </tt>");
            $graph->AddGPrint("in_last", '<tt>  %4.1lf%s</tt>');
            $graph->AddGPrint("in_avg",  '<tt>  %4.1lf%s</tt>');
            $graph->AddGPrint("in_max",  '<tt>  %4.1lf%s</tt>\n');
            
            $graph->AddLine(1, "out_bits", self::COLOR_OUBOUND, "<tt>Out:   </tt>");
            $graph->AddGPrint("out_last", '<tt>  %4.1lf%s</tt>');
            $graph->AddGPrint("out_avg",  '<tt>  %4.1lf%s</tt>');
            $graph->AddGPrint("out_max",  '<tt>  %4.1lf%s</tt>\n');
            
            if (CONFIG::$RRD_DEFAULT_FONT_PATH)
            	$graph->AddFont("DEFAULT", "0", CONFIG::$RRD_DEFAULT_FONT_PATH);
            
            $graph->Plot($image_path, "-86400", false, 
                            array(
                                    "--pango-markup",
                            		"-v", "Bits per second", 
                                    "-t", "Network usage",
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