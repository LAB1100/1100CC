<?php
	
	if (DB::ENGINE == DB::ENGINE_POSTGRESQL) {
		
		DB::setConnectionDetails('localhost', 'postgres', './database_postgresql.pass', DB::CONNECT_CMS, 'CC1100');
		DB::setConnectionDetails('localhost', 'postgres', './database_postgresql.pass', DB::CONNECT_HOME, 'CC1100');
		
		DB::setConnectionAlias(false, 'CC1100');
	} else {
		
		DB::setConnectionDetails('localhost', '1100CC_cms', './database_cms.pass', DB::CONNECT_CMS);
		DB::setConnectionDetails('localhost', '1100CC_home', './database_home.pass', DB::CONNECT_HOME);
	}
