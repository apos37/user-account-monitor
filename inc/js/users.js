jQuery( $ => {
    console.log( 'User Account Monitor Users JS Loaded...' );

    let countFlagged = uamonitor_users.already_flagged;
    const currentURL = new URL( window.location.href );
    const params = currentURL.searchParams;
    const nonceScan = uamonitor_users.nonce_scan;
    const doScan = params.get( 'uamonitor' ) === 'true' && params.get( '_wpnonce' ) === nonceScan;

    /**
     * Hide cleared
     */
    const hideCleared = uamonitor_users.hide_cleared;
    if ( doScan && hideCleared ) {
        $( '#the-list' ).addClass( 'hide-cleared' );
    }


    /**
     * Loaded
     */

    $( '#the-list tr' ).each( function () {
        const row = $( this );
        const suspiciousCell = row.find( '.column-suspicious [data-suspicious-status]' );
        const status = suspiciousCell.data( 'suspicious-status' );

        if ( status === 'cleared' || status === 'flagged' ) {
            row.addClass( status );
        }
    } );
    

    /**
     * Scan Button
     */

    let btnURL, btnText;

    if ( doScan ) {
        // Remove query args
        params.delete( 'uamonitor' );
        params.delete( '_wpnonce' );
        btnText = uamonitor_users.text.stop;
    } else {
        // Add query args
        params.set( 'uamonitor', 'true' );
        params.set( '_wpnonce', nonceScan );
        btnText = uamonitor_users.text.start;
    }

    // Reconstruct URL
    btnURL = currentURL.origin + currentURL.pathname + '?' + params.toString();

    $( '.wrap > a.page-title-action' ).after(
        `<a id="uamonitor-run-scan" href="${btnURL}" class="page-title-action" style="margin-left: 10px;"><span class="text">${btnText}</span><span class="done"></span></a>`
    );


    /**
     * Run Scan
     */

    // Scan an individual user
    const scanUser = async ( userID, single = false ) => {
        // console.log( `Scanning user (${userID})...` );

        // Add scanning class
        var userRow = $( `input[type="checkbox"][value="${userID}"]` ).closest( 'tr' );
        userRow.addClass( 'scanning' );

        // Say it in the table cell
        var suspicious = userRow.find( '.column-suspicious' );
        suspicious.html( `<em>${uamonitor_users.text.scanning}</em>` );

        // Run the scan
        const start = performance.now();
        const result = await $.ajax( {
            type: 'post',
            dataType: 'json',
            url: uamonitor_users.ajaxurl,
            data: { 
                action: 'uamonitor_scan', 
                nonce: nonceScan,
                userID: userID,
                single: single
            }
        } );
        const end = performance.now();
        console.log( `User ${userID} scanned in ${(end - start).toFixed(2)}ms` );
        return result;
    }

    if ( doScan ) {

        // Remove the indicators if we've started scanning
        removeIndicators();

        // Scan all link on a post
        const scanUsers = async () => {
            console.log( `Scanning users on page...` );
            const start = performance.now();

            // Get the user rows
            const userRows = $( '#the-list tr' );

            // Count users checked
            var countDone = 0;
            const totalUsersOnPage = userRows.length;

            // Iter the rows
            for ( const row of userRows ) {
                const userRow = $( row );
                var tableCell = userRow.find( '.column-suspicious' );

                const userID = userRow.find( 'th.check-column input[type="checkbox"]' ).val();
                if ( userID ) {

                    // Skip if already cleared
                    if ( !uamonitor_users.recheck_cleared && tableCell.find( '[data-suspicious-status="cleared"]' ).length ) {
                        userRow.addClass( 'cleared' );
                        countDone++;
                        const percent = ( countDone / totalUsersOnPage ) * 100;
                        $( '#uamonitor-run-scan .done' ).html( ` ${percent.toFixed(0)}% (${countDone}/${totalUsersOnPage}) – ${countFlagged} flagged` );
                        continue;
                    }

                    // Start the scan
                    const response = await scanUser( userID );

                    // Remove scanning class
                    userRow.removeClass( 'scanning' );

                    // Update
                    if ( response.success ) {
                        const suspicious = response.data.suspicious;

                        if ( suspicious === 'cleared' ) {
                            tableCell.html( `<em data-suspicious-status="cleared" style="color: green">${uamonitor_users.text.cleared}</em>` );
                            userRow.addClass( 'cleared' );
                        } else if ( suspicious ) {
                            tableCell.html( `<span class="flags">${suspicious.join( ', ' )}</span>` );
                            userRow.addClass( 'flagged' );
                            userRow.find( 'th.check-column input[type="checkbox"]' ).prop( 'checked', true );
                            countFlagged++;
                            updateIndicatorCounts( countFlagged );
                        }
                    } else {
                        const errorMsg = response.data?.msg || uamonitor_users.text.error;
                        tableCell.html( `<em data-suspicious-status="error" style="color: red">${errorMsg}</em>` );
                        userRow.addClass( 'error' );
                    }

                    // Increase count
                    countDone++;
                    const percent = ( countDone / totalUsersOnPage ) * 100;
                    $( '#uamonitor-run-scan .done' ).html( ` ${percent.toFixed(0)}% (${countDone}/${totalUsersOnPage}) – ${countFlagged} flagged` );
                    if ( percent == 100 ) {
                        $( '#uamonitor-run-scan .text' ).html( uamonitor_users.text.complete );
                    }
                }
            };

            // Stop timing
            const end = performance.now();
            const seconds = ( end - start ) / 1000;
            return console.log( `Scanning complete in ${seconds.toFixed( 2 )}s` );
        }

        // Do it
        scanUsers();
    }


    /**
     * Clear a User Manually
     */
    $( document ).on( 'click', '.uamonitor-clear, .uamonitor-flag', function( e ) {
        e.preventDefault();

        const link     = $( this );
        const userID   = link.data( 'userid' );
        const method   = link.data( 'method' );
        const nonce    = uamonitor_users.nonce_clear;
        const userRow = $( `input[type="checkbox"][value="${userID}"]` ).closest( 'tr' );
        const tableCell = userRow.find( '.column-suspicious' );

        $.post( uamonitor_users.ajaxurl, {
            action: 'uamonitor_clear',
            method: method,
            nonce: nonce,
            userID: userID
        }, response => {
            if ( response.success ) {
                if ( method === 'clear' ) {
                    tableCell.html( `<em data-suspicious-status="cleared" style="color: green;">${uamonitor_users.text.cleared}</em>` );
                    userRow.removeClass( 'flagged error' ).addClass( 'cleared' );
                    userRow.find( 'th.check-column input[type="checkbox"]' ).prop( 'checked', false );

                    countFlagged = Math.max( 0, countFlagged - 1 );
                    updateIndicatorCounts( countFlagged );

                    link
                        .removeClass( 'uamonitor-clear' )
                        .addClass( 'uamonitor-flag' )
                        .data( 'method', 'flag' )
                        .attr( 'data-method', 'flag' )
                        .text( uamonitor_users.text.mark_flag );
                } else if ( method === 'flag' ) {
                    tableCell.html( `<strong data-suspicious-status="flagged" style="color: red;">${uamonitor_users.text.manual}</strong>` );
                    userRow.removeClass( 'cleared error' ).addClass( 'flagged' );
                    userRow.find( 'th.check-column input[type="checkbox"]' ).prop( 'checked', true );

                    countFlagged++;
                    updateIndicatorCounts( countFlagged );

                    link
                        .removeClass( 'uamonitor-flag' )
                        .addClass( 'uamonitor-clear' )
                        .data( 'method', 'clear' )
                        .attr( 'data-method', 'clear' )
                        .text( uamonitor_users.text.mark_clear );
                }

                const totalRows = $( '#the-list tr' ).length;
                const doneCount = $( '#the-list tr.cleared, #the-list tr.flagged, #the-list tr.error' ).length;
                const percent = ( doneCount / totalRows ) * 100;
                $( '#uamonitor-run-scan .done' ).html( ` ${percent.toFixed(0)}% (${doneCount}/${totalRows}) – ${countFlagged} flagged` );
                
            } else {
                alert( response.data?.msg || 'Error updating user.' );
            }
        } );
    } );


    // Update the indicators and notice
    function updateIndicatorCounts( countFlagged ) {
        if ( countFlagged == 0 ) {
            removeIndicators();
        } else {
            $( '#uamonitor-flagged-count' ).text( countFlagged );
            $( '.uamonitor-flagged-count .flagged-count' ).text( countFlagged );
        }
    }

    function removeIndicators() {
        $( '.uamonitor-flagged-notice, .uamonitor-flagged-count' ).remove();
    }


    /**
     * Scan a Single User Manually
     */
    $( document ).on( 'click', '.uamonitor-scan', async function( e ) {
        e.preventDefault();

        const link = $( this );
        const userID = link.data( 'userid' );
        const userRow = $( `#user-${userID}` );
        const tableCell = userRow.find( '.column-suspicious' );

        const response = await scanUser( userID, true );
        userRow.removeClass( 'scanning' );

        if ( response.success ) {
            const suspicious = response.data.suspicious;

            if ( suspicious === 'cleared' ) {
                tableCell.html( `<em data-suspicious-status="cleared" style="color: green">${uamonitor_users.text.cleared}</em>` );
                userRow.removeClass( 'flagged error' ).addClass( 'cleared' );
                userRow.find( 'th.check-column input[type="checkbox"]' ).prop( 'checked', false );
            } else if ( suspicious ) {
                tableCell.html( `<span class="flags">${suspicious.join( ', ' )}</span>` );
                userRow.removeClass( 'cleared error' ).addClass( 'flagged' );
                userRow.find( 'th.check-column input[type="checkbox"]' ).prop( 'checked', true );
                countFlagged++;
                updateIndicatorCounts( countFlagged );
            }
        } else {
            const errorMsg = response.data?.msg || uamonitor_users.text.error;
            tableCell.html( `<em data-suspicious-status="error" style="color: red">${errorMsg}</em>` );
            userRow.removeClass( 'cleared flagged' ).addClass( 'error' );
        }
    } );


} );