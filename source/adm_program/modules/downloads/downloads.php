<?php
/******************************************************************************
 * Show a list of all downloads
 *
 * Copyright    : (c) 2004 - 2013 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Parameters:
 *
 * folder_id : akutelle OrdnerId
 *
 *****************************************************************************/

require_once('../../system/common.php');
require_once('../../system/classes/module_menu.php');
require_once('../../system/classes/table_folder.php');
require_once('../../system/file_extension_icons.php');
require_once('../../system/classes/html_table.php');
unset($_SESSION['download_request']);

// Initialize and check the parameters
$getFolderId = admFuncVariableIsValid($_GET, 'folder_id', 'numeric', 0);

// Check if module is activated
if ($gPreferences['enable_download_module'] != 1)
{
    // Module is not activated
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
}

// Only available from master organization
if (strcasecmp($gCurrentOrganization->getValue('org_shortname'), $g_organization) != 0)
{
    // is not master organization
    $gMessage->show($gL10n->get('SYS_MODULE_ACCESS_FROM_HOMEPAGE_ONLY', $gHomepage));
}

// Session handling
$gNavigation->clear();
$gNavigation->addUrl(CURRENT_URL);

try
{
    // get recordset of current folder from databse
    $currentFolder = new TableFolder($gDb);
    $currentFolder->getFolderForDownload($getFolderId);
}
catch(AdmException $e)
{
	$e->showHtml();
}

$getFolderId = $currentFolder->getValue('fol_id');

// Get folder content for style
$folderContent = $currentFolder->getFolderContentsForDownload();

// Keep navigation link 
$navigationBar = $currentFolder->getNavigationForDownload();



// Define html header
$gLayout['title']  = $gL10n->get('DOW_DOWNLOADS');
$gLayout['header'] = '
    <script type="text/javascript" src="'.$g_root_path.'/adm_program/libs/tooltip/text_tooltip.js"></script>
    <script type="text/javascript"><!--
        $(document).ready(function() 
        {
            $("a[rel=\'lnkDelete\']").colorbox({rel:\'nofollow\', scrolling:false, onComplete:function(){$("#admButtonNo").focus();}});
        }); 
    //--></script>';
    
// create module menu
$DownloadsMenu = new ModuleMenu('admMenuDownloads');

if ($gCurrentUser->editDownloadRight())
{
    // show links for upload, create folder and folder configuration
    $DownloadsMenu->addItem('admMenuItemCreateFolder', $g_root_path.'/adm_program/modules/downloads/folder_new.php?folder_id='.$getFolderId,
                        $gL10n->get('DOW_CREATE_FOLDER'), 'folder_create.png' );

    $DownloadsMenu->addItem('admMenuItemAddFile', $g_root_path.'/adm_program/modules/downloads/upload.php?folder_id='.$getFolderId,
                        $gL10n->get('DOW_UPLOAD_FILE'), 'page_white_upload.png' );

    $DownloadsMenu->addItem('admMenuItemConfigFolder', $g_root_path.'/adm_program/modules/downloads/folder_config.php?folder_id='.$getFolderId,
                        $gL10n->get('SYS_AUTHORIZATION'), 'lock.png' );
};

if($gCurrentUser->isWebmaster())
{
	// show link to system preferences of weblinks
	$DownloadsMenu->addItem('admMenuItemPreferencesLinks', $g_root_path.'/adm_program/administration/organization/organization.php?show_option=DOW_DOWNLOADS', 
						$gL10n->get('SYS_MODULE_PREFERENCES'), 'options.png');
}

//Create table object
$downloadOverview = new htmlTable();
$downloadOverview->setAttribute('class', 'tableList', 'table')
                 ->setAttribute('cellspacing', '0', 'table')
                 ->setElement('thead')
                 ->setElement('tr')
                 ->setElement('th')
                 ->setAttribute('style', 'width: 25px;', 'th')
                 ->setData('<img class="iconInformation" src="'. THEME_PATH. '/icons/download.png" alt="'.$gL10n->get('SYS_FOLDER').' / '.$gL10n->get('DOW_FILE_TYPE').'" title="'.$gL10n->get('SYS_FOLDER').' / '.$gL10n->get('DOW_FILE_TYPE').'" />')
                 ->setElement('th', $gL10n->get('SYS_NAME'))
                 ->setElement('th', $gL10n->get('SYS_DATE_MODIFIED'))
                 ->setElement('th', $gL10n->get('SYS_SIZE'))
                 ->setElement('th', $gL10n->get('DOW_COUNTER'));

if ($gCurrentUser->editDownloadRight())
{
    $downloadOverview->setElement('th')
                     ->setAttribute('style', 'text-align: center;', 'th')
                     ->setData($gL10n->get('SYS_FEATURES'))
                     ->setElement('tbody');
}

// If folder is empty
if (count($folderContent) == 0)
{
    if ($gCurrentUser->editDownloadRight())
    {
        $colspan = '6';
    }
    else
    {
        $colspan = '5';
    }

    $downloadOverview->setElement('tr')
                     ->setElement('td')
                     ->setAttribute('colspan', $colspan, 'td')
                     ->setData($gL10n->get('DOW_FOLDER_NO_FILES'));
}
else
{
    // Get folder content
    if (isset($folderContent['folders'])) 
    {
        // First get possible sub folders
        for($i=0; $i<count($folderContent['folders']); $i++) 
        {

            $nextFolder = $folderContent['folders'][$i];
            $downloadOverview->setElement('tr')
                             ->setAttribute('id', 'row_folder_'.$nextFolder['fol_id'].'', 'tr')
                             ->setAttribute('class', 'tableMouseOver', 'tr')
                             ->setElement('td', '<a class="iconLink" href="'.$g_root_path.'/adm_program/modules/downloads/downloads.php?folder_id='. $nextFolder['fol_id']. '">
                                                    <img src="'. THEME_PATH. '/icons/download.png" alt="'.$gL10n->get('SYS_FOLDER').'" title="'.$gL10n->get('SYS_FOLDER').'" /></a>')
                             ->setElement('td', '<a href="'.$g_root_path.'/adm_program/modules/downloads/downloads.php?folder_id='. $nextFolder['fol_id']. '">'. $nextFolder['fol_name']. '</a>'
                                                .(($nextFolder['fol_description']!='') ? '<span class="iconLink" ><a class="textTooltip" title="'.$nextFolder['fol_description'].'" href="#"><img src="'. THEME_PATH. '/icons/info.png" alt="'.$gL10n->get('SYS_FOLDER').'"/></a></span>'
                                                                                       : ''))
                             ->setElement('td', '&nbsp;')
                             ->setElement('td', '&nbsp;')
                             ->setElement('td', '&nbsp;');
                             
                if ($gCurrentUser->editDownloadRight())
                {
                    //Links for change and delete
                    $downloadOverview->setElement('td')
                                     ->setAttribute('style', 'text-align: center;', 'td')
                                     ->setData('<a class="iconLink" href="'.$g_root_path.'/adm_program/modules/downloads/rename.php?folder_id='. $nextFolder['fol_id']. '">
                                                <img src="'. THEME_PATH. '/icons/edit.png" alt="'.$gL10n->get('SYS_EDIT').'" title="'.$gL10n->get('SYS_EDIT').'" /></a>
                                                <a class="iconLink" rel="lnkDelete" href="'.$g_root_path.'/adm_program/system/popup_message.php?type=fol&amp;element_id=row_folder_'.
                                                $nextFolder['fol_id'].'&amp;name='.urlencode($nextFolder['fol_name']).'&amp;database_id='.$nextFolder['fol_id'].'"><img
                                                src="'. THEME_PATH. '/icons/delete.png" alt="'.$gL10n->get('SYS_DELETE').'" title="'.$gL10n->get('SYS_DELETE').'" /></a>'.
                                                ((!$nextFolder['fol_exists']) ? '<a rel="colorboxHelp" href="'. $g_root_path. '/adm_program/system/msg_window.php?message_id=DOW_FOLDER_NOT_EXISTS&amp;inline=true"><img
				                                                                onmouseover="ajax_showTooltip(event,\''.$g_root_path.'/adm_program/system/msg_window.php?message_id=DOW_FOLDER_NOT_EXISTS\',this)" onmouseout="ajax_hideTooltip()"
				                                                                class="iconHelpLink" src="'. THEME_PATH. '/icons/warning.png" alt="'.$gL10n->get('SYS_WARNING').'" /></a>'
                                                                            : ''));
                }
        }
    }

    // Get contained files
    if (isset($folderContent['files'])) {
        for($i=0; $i<count($folderContent['files']); $i++) {

            $nextFile = $folderContent['files'][$i];

            // Check filetyp
            $fileExtension  = admStrToLower(substr($nextFile['fil_name'], strrpos($nextFile['fil_name'], '.')+1));

            // Choose icon for the file
            $iconFile = 'page_white_question.png';
            if(array_key_exists($fileExtension, $icon_file_extension))
            {
                $iconFile = $icon_file_extension[$fileExtension];
            }
            
            // Format timestamp
            $timestamp = new DateTimeExtended($nextFile['fil_timestamp'], 'Y-m-d H:i:s');
            $downloadOverview->setElement('tr')
                             ->setAttribute('id', 'row_file_'.$nextFile['fil_id'].'', 'tr')
                             ->setAttribute('class', 'tableMouseOver', 'tr')
                             ->setElement('td', '<a class="iconLink" href="'.$g_root_path.'/adm_program/modules/downloads/get_file.php?file_id='. $nextFile['fil_id']. '">
                                                <img src="'. THEME_PATH. '/icons/'.$iconFile.'" alt="'.$gL10n->get('SYS_FILE').'" title="'.$gL10n->get('SYS_FILE').'" /></a>')
                             ->setElement('td', '<a href="'.$g_root_path.'/adm_program/modules/downloads/get_file.php?file_id='. $nextFile['fil_id']. '">'. $nextFile['fil_name']. '</a>'
                                                .(($nextFile['fil_description']!='') ? '<span class="iconLink" ><a class="textTooltip" title="'.$nextFile['fil_description'].'" href="#"><img src="'. THEME_PATH. '/icons/info.png" alt="'.$gL10n->get('SYS_FILE').'"/></a></span>'
                                                                                     : ''))
                             ->setElement('td', $timestamp->format($gPreferences['system_date'].' '.$gPreferences['system_time']))
                             ->setElement('td', $nextFile['fil_size']. ' kB&nbsp;')
                             ->setElement('td', ($nextFile['fil_counter'] != '') ? $nextFile['fil_counter'] : '&nbsp;');
                            
                if ($gCurrentUser->editDownloadRight())
                {
                    //Links for change and delete
                    $downloadOverview->setElement('td')
                                     ->setAttribute ('style', 'text-align: center;', 'td')
                                     ->setData('<a class="iconLink" href="'.$g_root_path.'/adm_program/modules/downloads/rename.php?file_id='. $nextFile['fil_id']. '">
                                                <img src="'. THEME_PATH. '/icons/edit.png" alt="'.$gL10n->get('SYS_EDIT').'" title="'.$gL10n->get('SYS_EDIT').'" /></a>
                                                    <a class="iconLink" rel="lnkDelete" href="'.$g_root_path.'/adm_program/system/popup_message.php?type=fil&amp;element_id=row_file_'.
                                                    $nextFile['fil_id'].'&amp;name='.urlencode($nextFile['fil_name']).'&amp;database_id='.$nextFile['fil_id'].'"><img
                                                    src="'. THEME_PATH. '/icons/delete.png" alt="'.$gL10n->get('SYS_DELETE').'" title="'.$gL10n->get('SYS_DELETE').'" /></a>'
                                                    .((!$nextFile['fil_exists']) ? '<a rel="colorboxHelp" href="'. $g_root_path. '/adm_program/system/msg_window.php?message_id=DOW_FILE_NOT_EXISTS&amp;inline=true"><img
                                                    				                onmouseover="ajax_showTooltip(event,\''.$g_root_path.'/adm_program/system/msg_window.php?message_id=DOW_FILE_NOT_EXISTS\',this)" onmouseout="ajax_hideTooltip()"
                                                    				                class="iconHelpLink" src="'. THEME_PATH. '/icons/warning.png" alt="'.$gL10n->get('SYS_WARNING').'" /></a>'
                                                                                 : ''));
                }
        }
    }

}

//Create download table
$htmlDownloadOverview = $downloadOverview->getHtmlTable();

//If user is download Admin show further files contained in this folder.
if ($gCurrentUser->editDownloadRight())
{
    // Check whether additional content was found in the folder
    if (isset($folderContent['additionalFolders']) || isset($folderContent['additionalFiles']))
    {

        $htmlAdminTableHeadline = '<h3>
                                    '.$gL10n->get('DOW_UNMANAGED_FILES').'
		                              <a rel="colorboxHelp" href="'. $g_root_path. '/adm_program/system/msg_window.php?message_id=DOW_ADDITIONAL_FILES&amp;inline=true"><img 
                                        onmouseover="ajax_showTooltip(event,\''.$g_root_path.'/adm_program/system/msg_window.php?message_id=DOW_ADDITIONAL_FILES\',this)" onmouseout="ajax_hideTooltip()"
                                        class="iconHelpLink" src="'. THEME_PATH. '/icons/help.png" alt="Help" title="" /></a>            
                                    </h3>';

        $adminTable = new htmlTable();
        $adminTable->setAttribute('class', 'tableList', 'table')
                   ->setAttribute('cellspacing', '0', 'table')
                   ->setElement('tr')
                   ->setElement('th')
                   ->setAttribute('style', 'width: 25px;', 'th')
                   ->setData('<img class="iconInformation" src="'. THEME_PATH. '/icons/download.png" alt="'.$gL10n->get('SYS_FOLDER').' / '.$gL10n->get('DOW_FILE_TYPE').'" title="'.$gL10n->get('SYS_FOLDER').' / '.$gL10n->get('DOW_FILE_TYPE').'" />')
                   ->setElement('th', $gL10n->get('SYS_NAME'))
                   ->setElement('th')
                   ->setAttribute('style', 'text-align: right;', 'th') 
                   ->setData($gL10n->get('SYS_FEATURES'));

        // Get folders
        if (isset($folderContent['additionalFolders'])) 
        {
            for($i=0; $i<count($folderContent['additionalFolders']); $i++) 
            {

                $nextFolder = $folderContent['additionalFolders'][$i];

                $adminTable->setElement('tr')
                           ->setAttribute('class', 'tableMouseOver', 'tr')
                           ->setElement('td', '<img src="'. THEME_PATH. '/icons/download.png" alt="'.$gL10n->get('SYS_FOLDER').'" title="'.$gL10n->get('SYS_FOLDER').'" />')
                           ->setElement('td', $nextFolder['fol_name'])
                           ->setElement('td')
                           ->setAttribute('style', 'text-align: right;', 'td')
                           ->setData('<a class="iconLink" href="'.$g_root_path.'/adm_program/modules/downloads/download_function.php?mode=6&amp;folder_id='.$getFolderId.'&amp;name='. urlencode($nextFolder['fol_name']). '">
                                        <img src="'. THEME_PATH. '/icons/database_in.png" alt="'.$gL10n->get('DOW_ADD_TO_DATABASE').'" title="'.$gL10n->get('DOW_ADD_TO_DATABASE').'" /></a>');
            }
        }

        // Get files
        if (isset($folderContent['additionalFiles'])) 
        {
            for($i=0; $i<count($folderContent['additionalFiles']); $i++) 
            {

                $nextFile = $folderContent['additionalFiles'][$i];

                // Get filetyp
                $fileExtension  = admStrToLower(substr($nextFile['fil_name'], strrpos($nextFile['fil_name'], '.')+1));

                // Choose icon for the file
                $iconFile = 'page_white_question.png';
                if(array_key_exists($fileExtension, $icon_file_extension))
                {
                    $iconFile = $icon_file_extension[$fileExtension];
                }

                $adminTable->setElement('tr')
                           ->setAttribute('class', 'tableMouseOver', 'tr')
                           ->setElement('td', '<img src="'. THEME_PATH. '/icons/'.$iconFile.'" alt="'.$gL10n->get('SYS_FILE').'" title="'.$gL10n->get('SYS_FILE').'" /></a>')
                           ->setElement('td', $nextFile['fil_name'])
                           ->setAttribute('style', 'text-align: right;', 'td')
                           ->setData('<a class="iconLink" href="'.$g_root_path.'/adm_program/modules/downloads/download_function.php?mode=6&amp;folder_id='.$getFolderId.'&amp;name='. urlencode($nextFile['fil_name']). '">
                                        <img src="'. THEME_PATH. '/icons/database_in.png" alt="'.$gL10n->get('DOW_ADD_TO_DATABASE').'" title="'.$gL10n->get('DOW_ADD_TO_DATABASE').'" /></a>');
            }
        }
        $htmlAdminTable = $adminTable->getHtmlTable();
    }
}

// Output module html to client
require(SERVER_PATH. '/adm_program/system/overall_header.php');

echo '<h1 class="moduleHeadline">'.$gLayout['title'].'</h1>';

$DownloadsMenu->show();

echo $navigationBar, $htmlDownloadOverview;
// if user has admin download rights, then show admin table for undefined files in folders
if(isset($htmlAdminTable))
{
    echo $htmlAdminTableHeadline, $htmlAdminTable;
}

require(SERVER_PATH. '/adm_program/system/overall_footer.php');

?>