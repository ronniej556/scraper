<?php

//get encoded email from a page

foreach($html->find('a') as $item)
{
    if(strpos($item->href, '/cdn-cgi/l/email-protection#') !== false)
    {
        $values['email'] = cfDecodeEmail(str_replace('/cdn-cgi/l/email-protection#', '', $item->href));
    }
}

?>