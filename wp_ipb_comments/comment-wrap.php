<div class="comment_item_wrap">
    <div class="comment_left">
        <div class="user_avatar">
            <img src="<?= $commentMemberData->get_photo() ?>" alt=""/>
        </div>
        <div class="comment_author">
            <a href="<?= BridgeIpb4Wp::getProfileUrl($commentMemberData)  ?>"><?= $commentMemberData->members_seo_name ?></a>
        </div>
        <div class="comment_time">
            <a href="#comment-<?= $comment->comment_ID ?>">
                <?= $commentTimeStr ?>
            </a>
        </div>
    </div>

    <div class="comment_right">

        <div class="comment_text <?= $commentHide ?>">
            <?= wpautop($comment->comment_content) ?>
        </div>

        <?php if ($commentHide): ?>
            <div class="comment_hide_button">
                <button>Показать комментарий</button>
            </div>
        <?php endif; ?>




    </div>

 <div class="manage">

        <span class="tool reply" onClick="">
            <i class="ico reply"></i> Ответить
        </span>


        <span class="karma karma_wrap">
                        <?= $this->getCommentRatingTemplate($this->getCommentRating($comment->comment_ID))?>
            <span onClick="" class="karma tool minus <?= $ratingDisabledClass  ?>" <?= $ratingDisabled ?>>-</span>
                        <span onClick="" class="karma tool plus <?= $ratingDisabledClass  ?>" <?= $ratingDisabled ?>>+</span>
                    </span>

        <?php if (
            $commentMemberId ==  BridgeIpb4Wp::$ipbMember->member_id
            && strtotime($comment->comment_date_gmt) > (time()-180)):
            ?>
            <span onClick="" class="tool edit">
                            <i class="ico edit"></i> Редактировать
                        </span>

            <span onClick="" class="tool delete <?= ($commentsChildrens) ? 'disabled' : ''?>">
                            <i class="ico delete"></i> Удалить
            </span>


        <?php endif; ?>
    </div>

<div style="clear:both"></div>
    
</div>