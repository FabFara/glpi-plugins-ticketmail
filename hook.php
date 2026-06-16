<?php
/**
 * ---------------------------------------------------------------------
 *  ticketmail is a plugin to allows users to send ticket information by email
 *  ---------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of ticketmail.
 *
 *  ticketmail is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  ticketmail is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with ticketmail. If not, see <http://www.gnu.org/licenses/>.
 *  ---------------------------------------------------------------------
 *  @copyright Copyright © 2022-2024 probeSys'
 *  @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 *  @link      https://github.com/Probesys/glpi-plugins-ticketmail
 *  @link      https://plugins.glpi-project.org/#/plugin/ticketmail
 *  ---------------------------------------------------------------------
 */

function plugin_ticketmail_install()
{
    global $DB;

    $migration = new Migration(TICKETMAIL_VERSION);

    //Fresh install
    if (!$DB->tableExists('glpi_plugin_ticketmail_profiles')) {
        $query = "CREATE TABLE `glpi_plugin_ticketmail_profiles` (
                `id` int NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
                `show_ticketmail_onglet` char(1) collate utf8mb4_unicode_ci default NULL,
                PRIMARY KEY  (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        if (!$DB->query($query)) {
            trigger_error($DB->error(), E_USER_ERROR);
        }

        $migration->executeMigration();

        include_once(PLUGIN_TICKETMAIL_DIR."/inc/profile.class.php");
        PluginTicketmailProfile::createAdminAccess($_SESSION['glpiactiveprofile']['id']);
    }
    //Update
    else {
        // Since v0.84 remove "profiles_id" column and use "id"
        if ($DB->fieldExists('glpi_plugin_ticketmail_profiles', 'profiles_id')) {
            $queries = [
                "ALTER TABLE glpi_plugin_ticketmail_profiles DROP COLUMN `id`;",
                "ALTER TABLE glpi_plugin_ticketmail_profiles
                    CHANGE profiles_id id int NOT NULL default '0'
                    COMMENT 'RELATION to glpi_profiles (id)';",
                "ALTER TABLE glpi_plugin_ticketmail_profiles ADD PRIMARY KEY (id);",
                "ALTER TABLE glpi_plugin_ticketmail_profiles DROP INDEX profiles_id;",
            ];
            foreach ($queries as $q) {
                if (!$DB->query($q)) {
                    trigger_error($DB->error(), E_USER_ERROR);
                }
            }
        }
    }
    return true;
}

function plugin_ticketmail_uninstall()
{
    global $DB;

    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_ticketmail_profiles`;");

    return true;
}

function plugin_ticketmail_getPluginsDatabaseRelations()
{
    $plugin = new Plugin();
    if ($plugin->isActivated("ticketmail")) {
        return [
                "glpi_profiles" => ["glpi_plugin_ticketmail_profiles" => "id"]
               ];
    } else {
        return [];
    }
}
