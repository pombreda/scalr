<?php
	class AwsProblemsProcess implements IProcess
	{
		public $ThreadArgs;
		public $ProcessDescription = "Aws Errors Manager";
		public $Logger;

		public function __construct()
		{
			// Get Logger instance
			$this->Logger = Logger::getLogger(__CLASS__);
		}

		public function OnStartForking()
		{
			$this->ThreadArgs = array('http://status.aws.amazon.com/rss/CloudFront.rss', // CF is common for all locations
			// US location
			'http://status.aws.amazon.com/rss/EC2NoCal.rss',
			'http://status.aws.amazon.com/rss/EC2.rss',
			'http://status.aws.amazon.com/rss/RelationalDBServiceNoCal.rss',
			'http://status.aws.amazon.com/rss/RelationalDBService.rss',
			'http://status.aws.amazon.com/rss/S3NoCal.rss',
			'http://status.aws.amazon.com/rss/S3US.rss',
			// Europe location
			'http://status.aws.amazon.com/rss/EC2EU.rss',
			'http://status.aws.amazon.com/rss/RelationalDBServiceEU.rss',
			'http://status.aws.amazon.com/rss/S3EU.rss',
			// Asia Pacific location
			'http://status.aws.amazon.com/rss/EC2APac.rss',
			'http://status.aws.amazon.com/rss/RelationalDBServiceAPac.rss',
			'http://status.aws.amazon.com/rss/S3APac.rss'
			);
			
		}

		public function OnEndForking()
		{

		}
		public function StartThread($rssUrl)
		{
			// collect all unique rss messages from each rss link ($rss) in database
			try
			{
				$db = Core::GetDBInstance(null, true);
				$rss =  @simplexml_load_file($rssUrl);
				 
				if($rss)
				{
					foreach($rss->channel->item as $rssItem)
					{
						
						$mysqlRssDate  = date( 'Y-m-d H:i:s', strtotime($rssItem->pubDate));
						$rssItem->description = $this->CutLongStrings($rssItem->description);

						$db->Execute("INSERT INTO aws_errors SET 
							`guid` = ?,
							`title` = ?,
							`pub_date` = ?,
							`description` = ?
							ON DUPLICATE KEY UPDATE
							`description` = ?",
						array(
							$rssItem->guid,
							strip_tags($rssItem->title),
							$mysqlRssDate,
							strip_tags($rssItem->description),
							strip_tags($rssItem->description))
						);
					}
				}
			}
			catch(Exception $e)
			{
				$this->Logger->error(_($e->getMessage()));
			}
		}


		private function CutLongStrings($string, $length = 80)
		{
			$sentance = explode(".",$string,2);	// gets first sentence
			$firstSentence = $sentance[0];
			$string = "";

			// if sentence is very long (more then 80 chars)
			if(strlen($firstSentence) > $length)	 
			{
				$wordsCounter	= $length/8;
				$firstSentence	= explode(" ", $firstSentence, $wordsCounter); // explode $wordsCounter/8 words from it

				for($j = 0; $j < count($firstSentence)-1; $j++)
				{
					$string.= "{$firstSentence[$j]} ";
				}
				
				// last part of the sentece
				$string = trim($string);
				$string .= ". ";
			}
			else
			{
				// the only one sentence in description doesn't require a refference link
				$string = $firstSentence;
				$string .= ". ";
			}

			return $string;
		}
	}
