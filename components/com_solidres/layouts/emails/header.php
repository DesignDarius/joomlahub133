<?php
/**
 ------------------------------------------------------------------------
 SOLIDRES - Accommodation booking extension for Joomla
 ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 ------------------------------------------------------------------------
 */

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/layouts/com_solidres/emails/header.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

extract($displayData);

?>
<!-- Inliner Build Version 4380b7741bb759d6cb997545f3add21ad48f010b -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo $direction ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width"/>
    <title>
		<?php echo $asset->name ?>
    </title>
</head>
<body style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0; padding: 0;">
<style type="text/css">
    a:hover {
        color: #2795b6 !important;
    }

    a:active {
        color: #2795b6 !important;
    }

    a:visited {
        color: #2ba6cb !important;
    }

    h1 a:active {
        color: #2ba6cb !important;
    }

    h2 a:active {
        color: #2ba6cb !important;
    }

    h3 a:active {
        color: #2ba6cb !important;
    }

    h4 a:active {
        color: #2ba6cb !important;
    }

    h5 a:active {
        color: #2ba6cb !important;
    }

    h6 a:active {
        color: #2ba6cb !important;
    }

    h1 a:visited {
        color: #2ba6cb !important;
    }

    h2 a:visited {
        color: #2ba6cb !important;
    }

    h3 a:visited {
        color: #2ba6cb !important;
    }

    h4 a:visited {
        color: #2ba6cb !important;
    }

    h5 a:visited {
        color: #2ba6cb !important;
    }

    h6 a:visited {
        color: #2ba6cb !important;
    }

    table.button:hover td {
        background: #2795b6 !important;
    }

    table.button:visited td {
        background: #2795b6 !important;
    }

    table.button:active td {
        background: #2795b6 !important;
    }

    table.button:hover td a {
        color: #fff !important;
    }

    table.button:visited td a {
        color: #fff !important;
    }

    table.button:active td a {
        color: #fff !important;
    }

    table.button:hover td {
        background: #2795b6 !important;
    }

    table.tiny-button:hover td {
        background: #2795b6 !important;
    }

    table.small-button:hover td {
        background: #2795b6 !important;
    }

    table.medium-button:hover td {
        background: #2795b6 !important;
    }

    table.large-button:hover td {
        background: #2795b6 !important;
    }

    table.button:hover td a {
        color: #ffffff !important;
    }

    table.button:active td a {
        color: #ffffff !important;
    }

    table.button td a:visited {
        color: #ffffff !important;
    }

    table.tiny-button:hover td a {
        color: #ffffff !important;
    }

    table.tiny-button:active td a {
        color: #ffffff !important;
    }

    table.tiny-button td a:visited {
        color: #ffffff !important;
    }

    table.small-button:hover td a {
        color: #ffffff !important;
    }

    table.small-button:active td a {
        color: #ffffff !important;
    }

    table.small-button td a:visited {
        color: #ffffff !important;
    }

    table.medium-button:hover td a {
        color: #ffffff !important;
    }

    table.medium-button:active td a {
        color: #ffffff !important;
    }

    table.medium-button td a:visited {
        color: #ffffff !important;
    }

    table.large-button:hover td a {
        color: #ffffff !important;
    }

    table.large-button:active td a {
        color: #ffffff !important;
    }

    table.large-button td a:visited {
        color: #ffffff !important;
    }

    table.secondary:hover td {
        background: #d0d0d0 !important;
        color: #555;
    }

    table.secondary:hover td a {
        color: #555 !important;
    }

    table.secondary td a:visited {
        color: #555 !important;
    }

    table.secondary:active td a {
        color: #555 !important;
    }

    table.success:hover td {
        background: #457a1a !important;
    }

    table.alert:hover td {
        background: #970b0e !important;
    }

    table.facebook:hover td {
        background: #2d4473 !important;
    }

    table.twitter:hover td {
        background: #0087bb !important;
    }

    table.google-plus:hover td {
        background: #CC0000 !important;
    }

    @media only screen and (max-width: 600px) {
        table[class="body"] img {
            width: auto !important;
            height: auto !important;
        }

        table[class="body"] center {
            min-width: 0 !important;
        }

        table[class="body"] .container {
            width: 95% !important;
        }

        table[class="body"] .row {
            width: 100% !important;
            display: block !important;
        }

        table[class="body"] .wrapper {
            display: block !important;
            padding-right: 0 !important;
        }

        table[class="body"] .columns {
            table-layout: fixed !important;
            float: none !important;
            width: 100% !important;
            padding-right: 0px !important;
            padding-left: 0px !important;
            display: block !important;
        }

        table[class="body"] .column {
            table-layout: fixed !important;
            float: none !important;
            width: 100% !important;
            padding-right: 0px !important;
            padding-left: 0px !important;
            display: block !important;
        }

        table[class="body"] .wrapper.first .columns {
            display: table !important;
        }

        table[class="body"] .wrapper.first .column {
            display: table !important;
        }

        table[class="body"] table.columns td {
            width: 100% !important;
        }

        table[class="body"] table.column td {
            width: 100% !important;
        }

        table[class="body"] .columns td.one {
            width: 8.333333% !important;
        }

        table[class="body"] .column td.one {
            width: 8.333333% !important;
        }

        table[class="body"] .columns td.two {
            width: 16.666666% !important;
        }

        table[class="body"] .column td.two {
            width: 16.666666% !important;
        }

        table[class="body"] .columns td.three {
            width: 25% !important;
        }

        table[class="body"] .column td.three {
            width: 25% !important;
        }

        table[class="body"] .columns td.four {
            width: 33.333333% !important;
        }

        table[class="body"] .column td.four {
            width: 33.333333% !important;
        }

        table[class="body"] .columns td.five {
            width: 41.666666% !important;
        }

        table[class="body"] .column td.five {
            width: 41.666666% !important;
        }

        table[class="body"] .columns td.six {
            width: 50% !important;
        }

        table[class="body"] .column td.six {
            width: 50% !important;
        }

        table[class="body"] .columns td.seven {
            width: 58.333333% !important;
        }

        table[class="body"] .column td.seven {
            width: 58.333333% !important;
        }

        table[class="body"] .columns td.eight {
            width: 66.666666% !important;
        }

        table[class="body"] .column td.eight {
            width: 66.666666% !important;
        }

        table[class="body"] .columns td.nine {
            width: 75% !important;
        }

        table[class="body"] .column td.nine {
            width: 75% !important;
        }

        table[class="body"] .columns td.ten {
            width: 83.333333% !important;
        }

        table[class="body"] .column td.ten {
            width: 83.333333% !important;
        }

        table[class="body"] .columns td.eleven {
            width: 91.666666% !important;
        }

        table[class="body"] .column td.eleven {
            width: 91.666666% !important;
        }

        table[class="body"] .columns td.twelve {
            width: 100% !important;
        }

        table[class="body"] .column td.twelve {
            width: 100% !important;
        }

        table[class="body"] td.offset-by-one {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-two {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-three {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-four {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-five {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-six {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-seven {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-eight {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-nine {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-ten {
            padding-left: 0 !important;
        }

        table[class="body"] td.offset-by-eleven {
            padding-left: 0 !important;
        }

        table[class="body"] table.columns td.expander {
            width: 1px !important;
        }

        table[class="body"] .right-text-pad {
            padding-left: 10px !important;
        }

        table[class="body"] .text-pad-right {
            padding-left: 10px !important;
        }

        table[class="body"] .left-text-pad {
            padding-right: 10px !important;
        }

        table[class="body"] .text-pad-left {
            padding-right: 10px !important;
        }

        table[class="body"] .hide-for-small {
            display: none !important;
        }

        table[class="body"] .show-for-desktop {
            display: none !important;
        }

        table[class="body"] .show-for-small {
            display: inherit !important;
        }

        table[class="body"] .hide-for-desktop {
            display: inherit !important;
        }

        table[class="body"] .right-text-pad {
            padding-left: 10px !important;
        }

        table[class="body"] .left-text-pad {
            padding-right: 10px !important;
        }
    }
</style>