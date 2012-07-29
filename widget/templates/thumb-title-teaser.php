<?php 
/**********************************************************************************************************************
File: 			plugins/custom-blurby/widget/templates/thumb-title-teaser.php
Description: 	Custom Story Widget News Template
Version: 		1.0
Author: 		Loushou
License: 		GPL2
Text Domain: 	custom-blurb-widget

REV HISTORY
[toy 8.30.11]	orig version
*************************************************************************************************************************/
// hello user--
// set up the following vars for this template
$titleMaxCharSize = 43;
$teaserMaxCharSize = 100;
$show_date = true;
$show_readMore = true;
$thumb_width = 100;
$thumb_height = 75;

// dev setup
$img_func = function_exists('cb_get_attachment_image') ? 'cb_get_attachment_image' : 'wp_get_attachment_image';
?>
<div class="thumb-title-teaser-blurb">
	<div class="blurb-header">
		<?php echo $before_title ?><?php echo $title ?><?php echo $after_title ?>
    </div>
    
	<div class="blurb-content">
        <ul class="blurb-post-list">
          <?php $count = 0; ?>
          <?php foreach ($posts as $i=>$post) {
					$content = $this->removeImagesfromContent($post->post_content);
					$content = (substr($content, 0,3)=="<a " ? $this->removeBlankLinksfromContent($content) : $content);
										
                    $post_title = (strlen($post->post_title)>$titleMaxCharSize ? substr($post->post_title,0,$titleMaxCharSize-1) ."..." : $post->post_title);
                    $teaser = ($post->post_excerpt=="" ? $content :  $post->post_excerpt);
                    $teaser = (strlen($teaser)>$teaserMaxCharSize ? substr($teaser,0,$teaserMaxCharSize-1) ."..." : $teaser);
          ?>
                <?php if ((isset($post->thumb) && !empty($post->thumb)) || (isset($post->thumb_URL) && !empty($post->thumb_URL))) {  ?>
    				<?php
						$imgSrc = ((isset($post->thumb) && !empty($post->thumb)) 
									? $img_func($post->thumb->ID, array($thumb_width, $thumb_height)) 
									: "<img src='".$post->thumb_URL."' id='blurb-default-thumbnail-".$post->ID."' width='".$thumb_width."' height='".$thumb_height."' class='blurb-default-thumbnail' />");
					?>
                        <li class="blurb-post-with-thumb blurb-post-<?php echo $i; ?>">
                            <div class="blurb-valign-fix-image"><h2><a href="<?php echo get_permalink($post->ID) ?>" title="Read More" class="blurb-image-keyhole blurb-<?php echo $thumb_width ?>x<?php echo $thumb_height ?>-keyhole"><?php echo $imgSrc ?></a></h2></div>
                            <?php if ($show_date) { ?>
	                            <div class="blurb-valign-date"><?php echo $post->post_date ?></div>
							<?php } ?>
                            <div class="blurb-valign-fix-title"><h2><a href="<?php echo get_permalink($post->ID) ?>" title="Read More"><?php echo $post_title ?></a></h2></div>
                            <div class="blurb-valign-teaser"><?php echo $teaser ?></div>
                            <?php if ($show_readMore) { ?>
	                            <div class="readMoreD"><a href="<?php echo get_permalink($post->ID) ?>">read more</a></div>
							<?php } ?>
                        </li>
                <?php } else { /** if we do not have a thumb to display */ ?>
                    <li class="blurb-post-title-only blurb-post-<?php echo $i; ?>">
                        <h2><a href="<?php echo get_permalink($post->ID) ?>" title="Read More"><?php echo $post_title ?></a></h2>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>
	</div>
    
	<?php if (isset($see_more) && !empty($see_more)): ?>
		<div class="blurb-bottom readMoreD"><a href="<?php echo $see_more ?>">See&nbsp;More;</a></div>
	<?php endif; ?>
    
    <div class="blurb-footer">
    </div>
</div>
<?php
?>