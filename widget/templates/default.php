<?php 
/**********************************************************************************************************************
File: 			plugins/custom-blurb/widget/templates/title-teaser.php
Description: 	custom-blurb title and teaster template
Version: 		1.0
Author: 		Loushou/toy
License: 		GPL2
Text Domain: 	

REV HISTORY
[toy 8.30.11]	orig version
*************************************************************************************************************************/
// hello user--
// set up the following vars for this template
$titleMaxCharSize = 243;
$teaserMaxCharSize = 150;
$show_date = true;
$show_readMore = true;
$thumb_width = 75;
$thumb_height = 100;
?>

<?php $img_func = function_exists('cb_get_attachment_image') ? 'cb_get_attachment_image' : 'wp_get_attachment_image'; ?>
<div class="default-blurb">
	<?php if (isset($title) && ($title!="")) { ?>
		<h3><div class="blurb-title"><?php echo $title; ?></div></h3>
	<?php } ?>
	<div class="blurb-content">
		<ul class="blurb-post-list">
			<?php $count = 0; ?>
			<?php foreach ($posts as $i=>$post){ ?>
    			<?php
    				$imgSrc = ((isset($post->thumb) && !empty($post->thumb)) 
    					? $img_func($post->thumb->ID, array($thumb_width, $thumb_height)) 
    					: get_the_post_thumbnail($post->ID, array($thumb_width, $thumb_height), array("id"=>"blurb-default-thumbnail-".$post->ID, "class"=>"blurb-default-thumbnail", "alt"=>$post->post_title)));

    				$content = $this->removeImagesfromContent($post->post_content);
    				$content = (substr($content, 0,3)=="<a " ? $this->removeBlankLinksfromContent($content) : $content);
    				$teaser = ($post->post_excerpt=="" ? $content :  $post->post_excerpt);
    				$teaser = (strlen($teaser)>$teaserMaxCharSize ? substr($teaser,0,$teaserMaxCharSize-1) ."..." : $teaser);
    			?>
				<li class="blurb-<?php echo $count++; ?> blurb-item">
					<div class="img"><a href="<?php echo get_permalink($post->ID) ?>" title="<?php echo stripslashes($post->post_title); ?>" ><?php echo $imgSrc ?></a></div>
					<div class="details">
						<a href="<?php echo get_permalink($post->ID) ?>" title="<?php echo stripslashes($post->post_title); ?>" >
							<h5><?php echo stripslashes($post->post_title); ?></h5>
						</a>
						<div class="teaser"><?php echo $teaser; ?></div>
					</div>
					<div class="clear"></div>
				</li>
			<?php  } ?>
		</ul>
	</div>

	<?php if (isset($see_more) && !empty($see_more)){ ?>
		<div class="blurb-bottom readMoreD"><a href="<?php echo $see_more ?>">See&nbsp;More</a></div>
	<?php } ?>
    
	<div class="blurb-footer"></div>
</div>