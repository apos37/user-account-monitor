jQuery( function ( $ ) {
    console.log( 'UAMonitor Quick Scan JS loaded...' );

    let running = false;
    let processedTotal = 0;
    let flaggedTotal   = 0;
    let lastId         = 0;
    let currentRequest = null; // store the current AJAX request

    function runBatch() {
        currentRequest = $.post( ajaxurl, {
            action: 'uamonitor_full_scan',
            nonce: uamonitor_quick_scan.nonce_scan,
            last_id: lastId,
            batch: uamonitor_quick_scan.batch_size
        }, function ( response ) {
            currentRequest = null;

            if ( !response.success ) {
                $( '#uamonitor-progress-text' ).text( 'Scan failed.' );
                running = false;
                $( '#uamonitor-start-scan' ).prop( 'disabled', false ).text( 'Run Full Scan' );
                $( '#uamonitor-cancel-scan' ).hide();
                return;
            }

            if ( !running ) {
                // cancelled during the request, do nothing
                return;
            }

            const data = response.data;

            processedTotal += parseInt( data.processed, 10 ) || 0;
            flaggedTotal   += parseInt( data.flagged_count, 10 ) || 0;

            $( '#uamonitor-progress-text' ).text(
                'Processed: ' + processedTotal + ' | Flagged: ' + flaggedTotal
            );

            const percent = Math.min( 100, ( processedTotal / uamonitor_quick_scan.total_users ) * 100 );
            $( '#uamonitor-progress-bar' ).css( 'width', percent + '%' );

            if ( data.done ) {
                $( '#uamonitor-progress-text' ).append( ' | Scan complete.' );
                running = false;
                $( '#uamonitor-start-scan' ).prop( 'disabled', false ).text( 'Run Full Scan' );
                $( '#uamonitor-cancel-scan' ).hide();

                // Add flagged users link
                if ( !$( '#uamonitor-view-flagged' ).length ) {
                    const flaggedUrl = '/wp-admin/users.php?suspicious=flagged&uamonitor_filter_nonce=' + uamonitor_quick_scan.nonce_filter;
                    $( '#uamonitor-scan-progress' ).append(
                        '<p><a id="uamonitor-view-flagged" class="button button-primary" href="' + flaggedUrl + '">View Flagged Users</a></p>'
                    );
                }
            } else {
                lastId = data.last_id;
                runBatch();
            }
        } );
    }

    $( '#uamonitor-start-scan' ).on( 'click', function () {
        if ( running ) return;
        running = true;
        processedTotal = 0;
        flaggedTotal   = 0;
        lastId         = 0;

        $( '.uamonitor-flagged-notice' ).remove();

        $( this ).prop( 'disabled', true ).text( 'Scanning... Please wait.' );
        $( '#uamonitor-cancel-scan' ).show();
        $( '#uamonitor-scan-progress' ).show();
        $( '#uamonitor-progress-bar' ).css( 'width', '0' );
        $( '#uamonitor-progress-text' ).text( 'Starting...' );
        runBatch();
    } );

    $( '#uamonitor-cancel-scan' ).on( 'click', function () {
        if ( currentRequest ) {
            currentRequest.abort(); // abort the in-progress AJAX request
            currentRequest = null;
        }
        running = false;
        $( '#uamonitor-start-scan' ).prop( 'disabled', false ).text( 'Run Full Scan' );
        $( this ).hide();
        $( '#uamonitor-progress-text' ).append( ' | Scan cancelled.' );
    } );
} );
