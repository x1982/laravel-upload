<?php
function landers_upload_convert_input( $data, bool $is_multi )
{
    if ( $data && is_string($data) ) {
        $data = json_decode($data, true);
    }

    if ( $data && !$is_multi ) {
        $data = $data[0];
    }

    if ( $data ) {
        $data = json_encode($data);
    }

    return $data;
}
