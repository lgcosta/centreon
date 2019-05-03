<?php
/*
 * Copyright 2005-2019 Centreon
 * Centreon is developed by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give Centreon
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of Centreon choice, provided that
 * Centreon also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 * SVN : $URL$
 * SVN : $Id$
 *
 */

require_once realpath(__DIR__ . "/../../../../../config/centreon.config.php");
require_once _CENTREON_PATH_ . "www/class/centreonDB.class.php";
include_once _CENTREON_PATH_ . "www/include/common/common-Func.php";
include_once _CENTREON_PATH_ . "www/include/reporting/dashboard/common-Func.php";
require_once _CENTREON_PATH_ . "www/class/centreonUser.class.php";
require_once _CENTREON_PATH_ . "www/class/centreonSession.class.php";
require_once _CENTREON_PATH_ . "www/class/centreon.class.php";
require_once _CENTREON_PATH_ . "www/class/centreonDuration.class.php";
include_once _CENTREON_PATH_ . "www/include/reporting/dashboard/DB-Func.php";

// DB Connexion
$pearDB = new CentreonDB();
$pearDBO = new CentreonDB("centstorage");

if (!isset($_SESSION["centreon"])) {
    CentreonSession::start();
    if (!CentreonSession::checkSession(session_id(), $pearDB)) {
        print "Bad Session";
        exit();
    }
}

$sid = session_id();
if (!empty($sid) && isset($_SESSION['centreon'])) {
    $oreon = $_SESSION['centreon'];
    $res = $pearDB->query(
        "SELECT user_id FROM session " .
        "WHERE user_id = '" . $pearDB->escape($oreon->user->user_id) . "'"
    );
    if (!$res->rowCount()) {
        get_error('bad session id');
    }
} else {
    get_error('need session id!');
}

$centreon = $oreon;

$servicegroupId = filter_var(
    $_GET['servicegroup'] ?? $_POST['servicegroup'] ?? null,
    FILTER_SANITIZE_STRING
);


/*
 * Getting time interval to report
 */
$dates = getPeriodToReport();
$start_date = htmlentities($_GET['start'], ENT_QUOTES, "UTF-8");
$end_date = htmlentities($_GET['end'], ENT_QUOTES, "UTF-8");
$servicegroup_name = getServiceGroupNameFromId($servicegroupId);

/*
 * file type setting
 */
header("Cache-Control: public");
header("Pragma: public");
header("Content-Type: application/octet-stream");
header("Content-disposition: filename=".$servicegroup_name.".csv");

echo _("ServiceGroup") . ";"
    . _("Begin date") . "; "
    . _("End date") . "; "
    . _("Duration") . "\n";

echo $servicegroup_name . ";"
    . date(_("d/m/Y H:i:s"), $start_date) . "; "
    . date(_("d/m/Y H:i:s"), $end_date) . "; "
    . ($end_date - $start_date) . "s\n\n";

echo "\n";

$stringMeanTime = _("Mean Time");
$stringAlert = _("Alert");
$stringOk = _("OK");
$stringWarning = _("Warning");
$stringCritical = _("Critical");
$stringUnknown = _("Unknown");
$stringDowntime = _("Scheduled Downtimes");
$stringUndetermined = _("Undetermined");

// Getting service group start
$reportingTimePeriod = getreportingTimePeriod();
$stats = array();
$stats = getLogInDbForServicesGroup(
    $servicegroupId,
    $start_date,
    $end_date,
    $reportingTimePeriod
);

echo _("Status") . ";"
    . _("Total Time") . ";"
    . $stringMeanTime . ";"
    . $stringAlert . "\n";

echo $stringOk . ";"
    . $stats["average"]["OK_TP"] . "%;"
    . $stats["average"]["OK_MP"] . "%;"
    . $stats["average"]["OK_A"] . ";\n";

echo $stringWarning . ";"
    . $stats["average"]["WARNING_TP"] . "%;"
    . $stats["average"]["WARNING_MP"] . "%;"
    . $stats["average"]["WARNING_A"] . ";\n";

echo $stringCritical . ";"
    . $stats["average"]["CRITICAL_TP"] . "%;"
    . $stats["average"]["CRITICAL_MP"] . "%;"
    . $stats["average"]["CRITICAL_A"] . ";\n";

echo $stringUnknown . ";"
    . $stats["average"]["UNKNOWN_TP"] . "%;"
    . $stats["average"]["UNKNOWN_MP"] . "%;"
    . $stats["average"]["UNKNOWN_A"] . ";\n";

echo $stringDowntime . ";"
    . $stats["average"]["MAINTENANCE_TP"] . "%;;;\n";

echo $stringUndetermined . ";"
    . $stats["average"]["UNDETERMINED_TP"] . "%;;;\n\n";

echo "\n\n";

// Services group services stats
echo _("Host") . ";"
    . _("Service") . ";"
    . $stringOk . " %;"
    . $stringOk . " " . $stringMeanTime . " %;"
    . $stringOk . " " . $stringAlert . ";"
    . $stringWarning . " %;"
    . $stringWarning . " " . $stringMeanTime . " %;"
    . $stringWarning . " " . $stringAlert . ";"
    . $stringCritical . " %;"
    . $stringCritical . " " . $stringMeanTime . " %;"
    . $stringCritical . " " . $stringAlert . ";"
    . $stringUnknown . " %;"
    . $stringUnknown . $stringMeanTime . " %;"
    . $stringUnknown . " " . $stringAlert . ";"
    . $stringDowntime . " %;"
    . $stringUndetermined . "\n";

foreach ($stats as $key => $tab) {
    if ($key != "average") {
        echo $tab["HOST_NAME"] . ";"
            . $tab["SERVICE_DESC"] . ";"
            . $tab["OK_TP"] . "%;"
            . $tab["OK_MP"] . "%;"
            . $tab["OK_A"] . ";"
            . $tab["WARNING_TP"] . "%;"
            . $tab["WARNING_MP"] . "%;"
            . $tab["WARNING_A"] . ";"
            . $tab["CRITICAL_TP"] . "%;"
            . $tab["CRITICAL_MP"] . "%;"
            . $tab["CRITICAL_A"] . ";"
            . $tab["UNKNOWN_TP"] . "%;"
            . $tab["UNKNOWN_MP"] . "%;"
            . $tab["UNKNOWN_A"] . ";"
            . $tab["MAINTENANCE_TP"] . " %;"
            . $tab["UNDETERMINED_TP"]. "%\n";
    }
}
echo "\n\n";

// Services group stats evolution
echo _("Day") . ";"
    . _("Duration") . ";"
    . $stringOk . " " . $stringMeanTime . ";"
    . $stringOk . " " . $stringAlert . ";"
    . $stringWarning . " " . $stringMeanTime . ";"
    . $stringWarning . " " . $stringAlert . ";"
    . $stringUnknown . " " . $stringMeanTime . ";"
    . $stringUnknown . " " . $stringAlert . ";"
    . $stringCritical . " " . $stringMeanTime . ";"
    . $stringCritical . " " . $stringAlert . ";"
    . _("Day") . "\n";

$dbResult = $pearDB->query(
    "SELECT `service_service_id` FROM `servicegroup_relation` " .
    "WHERE `servicegroup_sg_id` = '" . $servicegroupId . "'"
);

$str = "";
while ($sg = $dbResult->fetch()) {
    if ($str != "") {
        $str .= ", ";
    }
    $str .= "'" . $sg["service_service_id"] . "'";
}
$dbResult->closeCursor();
if ($str == "") {
    $str = "''";
}
unset($sg);
unset($dbResult);

$res = $pearDBO->query(
    "SELECT `date_start`, `date_end`, sum(`OKnbEvent`) as OKnbEvent, "
    . "sum(`CRITICALnbEvent`) as CRITICALnbEvent, "
    . "sum(`WARNINGnbEvent`) as WARNINGnbEvent, "
    . "sum(`UNKNOWNnbEvent`) as UNKNOWNnbEvent, "
    . "avg( `OKTimeScheduled` ) as OKTimeScheduled, "
    . "avg( `WARNINGTimeScheduled` ) as WARNINGTimeScheduled, "
    . "avg( `UNKNOWNTimeScheduled` ) as UNKNOWNTimeScheduled, "
    . "avg( `CRITICALTimeScheduled` ) as CRITICALTimeScheduled "
    . "FROM `log_archive_service` WHERE `service_id` IN (" . $str . ") "
    . "AND `date_start` >= '" . $start_date . "' "
    . "AND `date_end` <= '" . $end_date . "' "
    . "GROUP BY `date_end`, `date_start` order by `date_start` desc"
);
$statesTab = array("OK", "WARNING", "CRITICAL", "UNKNOWN");
while ($row = $res->fetch()) {
    $duration = $row["date_end"] - $row["date_start"];

    /* Percentage by status */
    $duration = $row["OKTimeScheduled"] + $row["WARNINGTimeScheduled"] + $row["UNKNOWNTimeScheduled"]
        + $row["CRITICALTimeScheduled"];

    $row["OK_MP"] = round($row["OKTimeScheduled"] * 100 / $duration, 2);
    $row["WARNING_MP"] = round($row["WARNINGTimeScheduled"] * 100 / $duration, 2);
    $row["UNKNOWN_MP"] = round($row["UNKNOWNTimeScheduled"] * 100 / $duration, 2);
    $row["CRITICAL_MP"] = round($row["CRITICALTimeScheduled"] * 100 / $duration, 2);

    echo $row["date_start"] . ";"
        . $duration . "s;"
        . $row["OK_MP"] . "%;"
        . $row["OKnbEvent"] . ";"
        . $row["WARNING_MP"] . "%;"
        . $row["WARNINGnbEvent"] . ";"
        . $row["UNKNOWN_MP"] . "%;"
        . $row["UNKNOWNnbEvent"] . ";"
        . $row["CRITICAL_MP"] . "%;"
        . $row["CRITICALnbEvent"] . ";"
        . date("Y-m-d H:i:s", $row["date_start"]) . ";\n";
}
$res->closeCursor();
