<?php do_action('uwp_template_before', 'users'); ?>
<div class="uwp-content-wrap">
    <div class="uwp-users-list">
        <div class="uwp-users-list-sort-search">
            <div class="uwp-user-search">
                <form method="get" class="searchform search-form" action="">
                    <input value="Search For" name="" class="s search-input" type="text"><input class="searchsubmit search-submit" value="Search" type="submit"><br>
                </form>
            </div>
            <div class="uwp-user-sort">
                <select name="sort_by" id="sort_by" onchange="javascript:window.location=this.value;"><option value="">Sort By:</option><option value="">Newer</option><option value="">Older</option><option value="">A-Z</option><option value="">Z-A</option></select>
            </div>
        </div>
        <ul class="uwp-users-list-wrap">
            <?php
            $users = uwp_get_users();

            foreach ($users as $user) {
                $user_obj = get_user_by('id', $user['id']);
                ?>
                <li class="uwp-users-list-user">
                    <div class="uwp-users-list-user-left">
                        <div class="uwp-users-list-user-avatar"><?php echo $user['avatar']; ?></div>
                    </div>
                    <div class="uwp-users-list-user-right">
                        <div class="uwp-users-list-user-name">
                            <h3><a href="<?php echo apply_filters('uwp_profile_link', $user['link'], $user['id']); ?>"><?php echo $user['name']; ?></a></h3>
                        </div>
                        <div class="uwp-users-list-user-social">
                            <?php do_action('uwp_profile_social', $user_obj ); ?>
                        </div>
                        <div class="uwp-users-list-user-bio">
                            <?php do_action('uwp_profile_bio', $user_obj ); ?>
                        </div>
                        <div class="clfx"></div>
                    </div>
                </li>
            <?php
            }
            ?>
        </ul>
    </div>
</div>
<?php do_action('uwp_template_after', 'users'); ?>