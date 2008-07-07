<?php
/**
 * This files instantiates the global {@link $DB} object and connects to the database.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @version $Id$
 */

/**
 * Load configuration
 * NOTE: fp> config should always be loaded as a whole because of the prequire"_once" stuff not working very well on Windows
 */
// require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * Load DB class
 */
require_once dirname(__FILE__).'/_core/model/db/_db.class.php';

/**
 * Database connection (connection opened here)
 *
 * @global DB $DB
 */
$DB = & new DB( $db_config );

/*
 * $Log$
 * Revision 1.11  2008/07/07 05:59:26  fplanque
 * minor / doc / rollback of overzealous indetation "fixes"
 *
 * Revision 1.10  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.9  2007/06/25 10:58:51  fplanque
 * MODULES (refactored MVC)
 *
 */
?>