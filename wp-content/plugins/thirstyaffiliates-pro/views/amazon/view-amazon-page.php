<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>

<div id="azon" class="wrap" data-active-indexes='<?php echo trim( json_encode( $active_search_indexes ) ); ?>'>

    <h2 style="margin-bottom: 20px;"><?php _e( 'Import Affiliate Links From Amazon' , 'thirstyaffiliates-pro' ); ?></h2>

    <div id="main-controls">

        <div id="search-controls">

            <h3 class="title"><?php _e( 'Search Amazon For Products' , 'thirstyaffiliates-pro' ); ?></h3>

            <?php if ( !empty( $error_message ) ) { ?>

                <p class="desc"><?php echo $error_message; ?></p>

            <?php } else { ?>

                <div class="field-set">
                    <input type="search" id="search-keywords" class="field" placeholder="<?php _e( 'Search For...' , 'thirstyaffiliates-pro' ); ?>" autocomplete="off">
                </div>

                <div class="field-set">
                    <select id="search-index" class="field" autocomplete="off">
                        <?php foreach( $default_search_index as $key => $text ) { ?>
                            <option value="<?php echo $key; ?>"><?php echo $text; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="field-set">
                    <select id="search-endpoint" class="field" autocomplete="off">
                        <?php foreach( $active_search_countries as $key => $text ) { ?>
                            <option value="<?php echo $key; ?>" <?php echo ( $last_used_search_endpoint === $key ) ? "selected" : ""; ?>><?php echo $text; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="field-set">
                    <input type="button" id="search-button" class="button button-primary" value="<?php _e( 'Search' , 'thirstyaffiliates-pro' ); ?>">
                    <span class="spinner"></span>
                </div>

            <?php } ?>

        </div>

        <div id="legend">

            <h3 class="title"><?php _e( 'Legend' , 'thirstyaffiliates-pro' ); ?></h3>

            <ul>
                <li class="import-link"><span class="icon"></span><span class="text"><?php _e( 'Import Link' , 'thirstyaffiliates-pro' ); ?></span></li>
                <li class="quick-import"><span class="icon"></span><span class="text"><?php _e( '1-Click Import Link' , 'thirstyaffiliates-pro' ); ?></span></li>
            </ul>

        </div>

        <div style="float: none; display: block; clear: both;"></div>
    </div><!--#main-controls-->

    <div id="search-results">

        <table id="search-results-table" class="wp-list-table widefat fixed striped posts" cellspacing="0" width="100%">

            <thead>
                <tr>
                    <th class="manage-column check-column sorting_disabled" style="padding: 10px 3px 6px 3px;"><input type="checkbox" id="select-all-search-results-top" class="select-all-search-results"></th>
                    <th class="image"><?php _e( 'Image' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="title"><?php _e( 'Title' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="price"><?php _e( 'Price' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="item-stock"><?php _e( 'Item Stock' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="sales-rank"><?php _e( 'Sales Rank' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="controls-column"><?php _e( 'Import Link' , 'thirstyaffiliates-pro' ); ?></th>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <th class="manage-column check-column sorting_disabled" style="padding: 10px 3px 6px 3px;"><input type="checkbox" id="select-all-search-results-top" class="select-all-search-results"></th>
                    <th class="image"><?php _e( 'Image' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="title"><?php _e( 'Title' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="price"><?php _e( 'Price' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="item-stock"><?php _e( 'Item Stock' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="sales-rank"><?php _e( 'Sales Rank' , 'thirstyaffiliates-pro' ); ?></th>
                    <th class="controls-column"><?php _e( 'Import Link' , 'thirstyaffiliates-pro' ); ?></th>
                </tr>
            </tfoot>

        </table><!-- #search-results-table -->

    </div><!--#search-results-->

</div><!--#azon-->