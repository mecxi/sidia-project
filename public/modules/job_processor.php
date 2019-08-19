<?php
/**
 * User: Mecxi
 * Date: 3/27/2017
 * Time: 9:31 PM
 * Job Processor Print handler
 */

require_once('../../config.php');

/* retrieve required parameters */
$requested_job = isset($_POST['job']) ? $_POST['job'] : null;
$service_id = isset($_POST['serviceID']) ? $_POST['serviceID'] : null;
$data_msic = array(isset($_POST['data']) ? $_POST['data'] : null);

switch($requested_job) {
    case 'CSV':
        /* job - print CSV */
        /* output headers so that the file is downloaded rather than displayed */
        $current_service = new services($service_id);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$current_service->name.'_REPORT.csv');

        /* create a file pointer connected to the output stream */
        $output = fopen('php://output', 'w');

        /* fetch data */
        $data_report = services::billing_timelines_details($service_id);
        if ($data_report) {
            /* find table header within the result */
            $column_headers = null;
            foreach ($data_report[0] as $col_head => $value) {
                $column_headers[] = $col_head;
            }

            /* output the column headings */
            fputcsv($output, $column_headers);

            /* insert data into CSV */
            for ($i = 0; $i < count($data_report); ++$i) {
                fputcsv($output, $data_report[$i]);
            }
        }

        break;
    case 'player_preview':
        /* export player preview draw engine */
        $date_print = strpos($data_msic[0], '|') !== false ? str_replace('|', '_', $data_msic[0]) : $data_msic[0];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=DRAW-ENGINE-PREVIEW-'.strtoupper($date_print).'_'. (new service_draw($service_id))->name.'.csv');
        print_requested_data(service_draw::get_players_preview($service_id, $data_msic[0], true));
        break;
    case 'player_winners':
        /* export player winners draw engine */
        $date_print = strpos($data_msic[0], '|') !== false ? str_replace('|', '_', $data_msic[0]) : $data_msic[0];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=DRAW-ENGINE-WINNERS-'.strtoupper($date_print).'_'. (new service_draw($service_id))->name.'.csv');
        print_requested_data(service_draw::fetch_draw_winners($service_id, $data_msic[0], true));

        break;
    case 'MSIC':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Service_error_log.txt');
        print_direct_requested($data_msic);
        break;
}


/* print data requested */
function print_requested_data($data){
    /* create a file pointer connected to the output stream */
    $output = fopen('php://output', 'w');

    if (!isset($data['error'])) {
        /* find table header within the result */
        $column_headers = null;
        foreach ($data[0] as $col_head => $value) {
            $column_headers[] = $col_head;
        }

        /* output the column headings */
        if ($column_headers){
            fputcsv($output, $column_headers);
        }

        /* insert data into CSV */
        for ($i = 0; $i < count($data); ++$i) {
            fputcsv($output, $data[$i]);
        }
    }
}

/* print direct requested */
function print_direct_requested($data){
    /* create a file pointer connected to the output stream */
    $output = fopen('php://output', 'w');
    if ($data){
        fputcsv($output, $data);
    }
}