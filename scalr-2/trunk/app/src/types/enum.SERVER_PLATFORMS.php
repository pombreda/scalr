<?
	final class SERVER_PLATFORMS
	{
		const EC2		= 'ec2';
		const RDS		= 'rds';
		
		//FOR FUTURE USE
		const RACKSPACE = 'rs';
		const VPS		= 'vps';
		const GOGRID	= 'gogrid';
		const CLOUDCOM	= 'cloud.com';
		const EUCA		= 'euca';
		const NOVACC	= 'novacc';
		
		
		public static function GetList()
		{
			return array(
				self::EC2 => 'Amazon EC2',
				self::RDS => 'Amazon RDS'
			);
		}
	}
?>