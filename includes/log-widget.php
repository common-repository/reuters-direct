<?php
    $remove_defaults_widgets = array(
        'dashboard_incoming_links' => array(
            'page'    => 'dashboard',
            'context' => 'normal'
        ),
        'dashboard_right_now' => array(
            'page'    => 'dashboard',
            'context' => 'normal'
        ),
        'dashboard_recent_drafts' => array(
            'page'    => 'dashboard',
            'context' => 'side'
        ),
        'dashboard_quick_press' => array(
            'page'    => 'dashboard',
            'context' => 'side'
        ),
        'dashboard_plugins' => array(
            'page'    => 'dashboard',
            'context' => 'normal'
        ),
        'dashboard_primary' => array(
            'page'    => 'dashboard',
            'context' => 'side'
        ),
        'dashboard_secondary' => array(
            'page'    => 'dashboard',
            'context' => 'side'
        ),
        'dashboard_recent_comments' => array(
            'page'    => 'dashboard',
            'context' => 'normal'
        )
    );

    $custom_dashboard_widgets = array(
        'my-dashboard-widget' => array(
            'title' => 'Reuters Direct Logs',
            'callback' => 'dashboardWidgetContent'
        )
    );

    function dashboardWidgetContent() {
        $user = wp_get_current_user();
        $log = __DIR__.'/logs/log_'.date('Y-m-d').'.txt';
        $color = ['info'=>'#444444', 'notice'=>'#0074A2', 'error'=>'#DC0A0A'];
        $log_rows = [];
        $handle = @fopen($log, "r");
        if($handle) {
            while(($buffer = fgets($handle, 4096)) !== false) {
                $log_rows[] = $buffer;
            }
            if(!feof($handle)) {
                echo "Error: unexpected fgets() fail";
            }
            fclose($handle);
        }
        $log_rows_rev = array_reverse($log_rows);
        echo '<div style="height:500px; overflow:auto;"><table style="display:block;">';
        foreach($log_rows_rev as $row) {
            $log_split = str_replace('[','',explode('] ', $row));
            echo '<tr style="font-size:12px;"><td style="min-width:150px; color:#777; vertical-align:top;">'.$log_split[0].'</td><td style="color:'.$color[$log_split[1]].';">'.$log_split[2].'</td></tr>';
        }
        echo '</table></div>';
    }
?>