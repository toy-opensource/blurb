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
$teaserMaxCharSize = 300;
$show_date = true;
$show_readMore = true;
$thumb_width = 100;
$thumb_height = 75;
?>
<div class="thumb-title-blurb">
	<?php if (isset($title) && ($title!="")) { ?>
		<h3><div class="blurb-title"><?php echo $title; ?></div></h3>
	<?php } ?>
	<div class="blurb-content">
		<ul class="blurb-post-list">
			<?php $count = 0; ?>
			<?php foreach ($posts as $i=>$post){			  
				$content = $this->removeImagesfromContent($post->post_content);
				$content = (substr($content, 0,3)=="<a " ? $this->removeBlankLinksfromContent($content) : $content);
				
				$post_title = (strlen($post->post_title)>$titleMaxCharSize ? substr($post->post_title,0,$titleMaxCharSize-1) ."..." : $post->post_title);
				$teaser = ($post->post_excerpt=="" ? $content :  $post->post_excerpt);
				$teaser = (strlen($teaser)>$teaserMaxCharSize ? substr($teaser,0,$teaserMaxCharSize-1) ."..." : $teaser);
			?>
			<li class="blurb-<?php echo $count++; ?>">
                <div class="blurb-title"><h2><a href="<?php echo get_permalink($post->ID) ?>" title="Read More"><?php echo $post_title ?></a></h2></div>
                <div class="blurb-teaser"><?php echo $teaser ?></div>
                <?php if ($show_readMore) { ?>
                    <div class="readMoreD"><a href="<?php echo get_permalink($post->ID) ?>"Read more</a></div>
				<?php } ?>
			</li>
            <?php } ?>
        </ul>
	</div>
    
	<?php if (isset($see_more) && !empty($see_more)): ?>
		<div class="blurb-bottom readMoreD"><a href="<?php echo $see_more ?>">See&nbsp;More</a></div>
	<?php endif; ?>
    
    <div class="blurb-footer">
    </div>
</div>
<?php
?>