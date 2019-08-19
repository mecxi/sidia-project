<!-- Small boxes (Stat box) | Overall subscription report  -->
<div class="row">
    <!-- New Subscribers -->
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-aqua" custom_box="total_new">
            <div class="inner">
                <h3>0</h3>

                <p>New Subscribers today</p>
            </div>
            <div class="icon">
                <i class="ion ion-ios-people-outline"></i>
            </div>
            <a href="<?php echo BASE_URI; ?>subscribers/new/" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- Totals Subscribers -->
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-green" custom_box="totalSubs">
            <div class="inner">
                <h3>0</h3>

                <p>Totals Subscribers</p>
            </div>
            <div class="icon">
                <i class="ion ion-ios-people-outline"></i>
            </div>
            <a href="<?php echo BASE_URI;?>subscribers/active/" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- Totals Portals Members  -->
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-yellow" custom_box="total_member">
            <div class="inner">
                <h3>0</h3>

                <p>Totals Membership</p>
            </div>
            <div class="icon">
                <i class="ion ion-ios-people-outline"></i>
            </div>
            <a href="<?php echo BASE_URI;?>subscribers/members/" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- Totals Un-subscribed  -->
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-red" custom_box="total_unsub">
            <div class="inner">
                <h3>0</h3>

                <p>Totals Un-subscribed </p>
            </div>
            <div class="icon">
                <i class="ion ion-ios-people-outline"></i>
            </div>
            <a href="<?php echo BASE_URI;?>subscribers/inactive/" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

