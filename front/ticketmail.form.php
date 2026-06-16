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

if (isset($_POST["send"])) {

    $header = "<!DOCTYPE html PUBLIC
                        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
                        <html>
                        <head>
                         <META http-equiv='Content-Type' content='text/html; charset='utf-8'>
                         </head>
                         <body>";
    $footer = "</body></html>";

    $mmail = new GLPIMailer();

    $from = $_POST['from'];
    $mmail->setFrom($_POST['from'], $_POST['from'], false);

    $body = str_replace("\\r", "", str_replace("\\n", "\n", html_entity_decode($_POST['body'])));
    $body = str_replace("\'", "'", $body);

    $hide_private_task = (array_key_exists('hide_private_task', $_POST) && $_POST['hide_private_task'] == '1') ? true : false;
    if ($hide_private_task) {
        $body = str_replace('<div class=\"is_private\" style=\"display: none;\">', 'PRIVATESTART', $body);
        $body = preg_replace('/PRIVATESTART[\s\S]+?<\/div>/', '', $body);
    }

    if (!empty($_POST['users_id_ticketmail'])) {
        $address = PluginTicketmailProfile::getEmail($_POST['users_id_ticketmail']);
    } else {
        $address = $_POST["address"];
    }

    if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
        Session::addMessageAfterRedirect(__("Invalid email address"), false, ERROR);
        Html::redirect($_SERVER['HTTP_REFERER']);
        return;
    }

    $mmail->addCustomHeader("Auto-Submitted: auto-generated");
    $mmail->addCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");
    $subject = $_POST["subject"];
    $mmail->addAddress($address, $address);
    $mmail->isHTML(true);
    $mmail->Subject = $subject;
    $mmail->Body = $header . GLPIMailer::normalizeBreaks($body) . $footer;
    $mmail->MessageID = "GLPI-ticketmail" . time() . "." . rand() . "@" . php_uname('n') . '-Ticket-' . $_POST['id'];

    if (!$mmail->send()) {
        Session::addMessageAfterRedirect(__("Your email could not be processed.\nIf the problem persists, contact the administrator"), false, ERROR);
        Toolbox::logError("Error during send email form ticketMail plugin:"
            . " RECIPIENT: " . $address
            . " SUBJECT: " . $subject
            . " ERROR: " . $mmail->ErrorInfo);
    } else {
        Toolbox::logDebug('[plugin ticketmail] : ' . sprintf(
            __('%1$s: %2$s'),
            sprintf(__('An email was sent to %s'), $address),
            $subject
        ));
        $changes[0] = 0;
        $changes[1] = $address;
        $changes[2] = $subject . '<br/>' . $body;

        Log::history($_POST['id'], 'Ticket', $changes, 'PluginTicketmailProfile', Log::HISTORY_PLUGIN + 1024);

        // Add new TicketTask
        $task = new TicketTask();
        $toadd = [
            "type"       => 'new',
            "tickets_id" => $_POST['id'],
            "actiontime" => 0,
            "state"      => Planning::DONE,
            "content"    => __('Send ticket information by email', 'ticketmail') . ' ' . __('to') . ' ' . $address
        ];
        $task->add($toadd);

        // Add Document txt with body content
        $file = 'ticketmailContent-' . $_POST['id'] . '-' . rand() . '.txt';
        $tmp_file = GLPI_TMP_DIR . "/" . $file;
        file_put_contents($tmp_file, $body);

        $document = new Document();
        $input = [
            "items_id"  => $task->getID(),
            "itemtype"  => 'TicketTask',
            "_filename" => [$file]
        ];
        $input = $document->prepareInputForAdd($input);
        if ($input) {
            $document->add($input);
            $docitem = new Document_Item();
            $docitem->add([
                'documents_id' => $document->getID(),
                'itemtype'     => 'TicketTask',
                'items_id'     => $task->getID()
            ]);
        }

        Session::addMessageAfterRedirect(sprintf(__('An email was sent to %s'), $address));
    }
    $mmail->clearAddresses();
    Html::redirect($_SERVER['HTTP_REFERER']);
} else {
    Html::redirect("../index.php");
}
