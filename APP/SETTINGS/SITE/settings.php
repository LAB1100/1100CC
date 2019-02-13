<?php
	
	if (DB::ENGINE == DB::ENGINE_POSTGRESQL) {
		
		DB::setConnectionDetails('localhost', 'postgres', '*PASSWORD*', DB::CONNECT_HOME, 'CC1100');
		DB::setConnectionDetails('localhost', 'postgres', '*PASSWORD*', DB::CONNECT_CMS, 'CC1100');
		
		DB::setConnectionAlias(false, 'CC1100');
	} else {
		
		DB::setConnectionDetails('localhost', '1100CC_home', '*PASSWORD*', DB::CONNECT_HOME);
		DB::setConnectionDetails('localhost', '1100CC_cms', '*PASSWORD*', DB::CONNECT_CMS);
	}
