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

include("../../../inc/includes.php");

$prof = new PluginTicketmailProfile();

if (isset($_POST['update_user_profile'])) {
    majDroit($_POST);
    Html::back();
}

/**
 * Update profile rights in the database
 */
function majDroit($arrayItem)
{
    global $DB;

    $id = (int)($arrayItem['id'] ?? 0);
    if ($id <= 0) {
        return;
    }

    $existing = $DB->request([
        'FROM'  => 'glpi_plugin_ticketmail_profiles',
        'WHERE' => ['id' => $id],
    ]);

    if (count($existing) > 0) {
        $DB->update(
            'glpi_plugin_ticketmail_profiles',
            ['show_ticketmail_onglet' => $arrayItem['show_ticketmail_onglet']],
            ['id' => $id]
        );
    }
}
