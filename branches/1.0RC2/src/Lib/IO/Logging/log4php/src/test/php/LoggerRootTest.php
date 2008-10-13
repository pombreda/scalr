<?php
/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @category   tests
 * @package    log4php
 * @author     Marco Vassura
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version    SVN: $Id$
 * @link       http://logging.apache.org/log4php
 */

require_once dirname(__FILE__).'/phpunit.php';

require_once LOG4PHP_DIR . '/LoggerRoot.php';

class LoggerRootTest extends PHPUnit_Framework_TestCase {
        
        private $loggerRoot;
        
        protected function setUp() {
                $this->loggerRoot = new LoggerRoot();
        }
        
        public function testIfLevelIsInitiallyLevelAll() {
                $this->assertEquals($this->loggerRoot->getLevel()->levelStr, 'ALL');
        }

        public function testIfNameIsRoot() {
                $this->assertEquals($this->loggerRoot->getName(), 'root');
        }

        public function testIfParentIsNull() {
                $this->assertSame($this->loggerRoot->getParent(), null);
        }

        public function testSetParent() {
                $this->loggerRoot->setParent('dummy');
                $this->testIfParentIsNull();
        }

}
?>
