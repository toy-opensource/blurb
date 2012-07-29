<?php 
/**********************************************************************************************************************
File: 			plugins/custom-blurb/widget/templates/video-table.php
Description: 	custom-blurb 2x2 template template
Version: 		1.0
Author: 		Loushou/toy
License: 		GPL2
Text Domain: 	

REV HISTORY
[toy 8.30.11]	orig version
*************************************************************************************************************************/
// hello user--
// set up the following vars for this template
$titleMaxCharSize = 15;
$teaserMaxCharSize = 100;
$show_date = true;
$show_readMore = true;
$thumb_width = 100;
$thumb_height = 75;
$posts_per_row = 2;
$vids_are_from_youtube= true;

// dev setup
$img_func = function_exists('cb_get_attachment_image') ? 'cb_get_attachment_image' : 'wp_get_attachment_image';
?>
<?php $img_func = function_exists('cb_get_attachment_image') ? 'cb_get_attachment_image' : 'wp_get_attachment_image'; ?>
<div class="video-blurb">
	<div class="blurb-header">
		<?php echo $before_title ?><?php echo $title ?><?php echo $after_title ?>
    </div>
    
    <div class="blurb-content">
        <ul class="blurb-post-list">

		<?php $count = 0; ?>
        <?php if (count($posts)>0) { ?>
            <table>
            <tbody>
            <tr>
			<?php foreach ($posts as $post){
                    $vid = get_post_custom($post->ID);
                    $post_title = (strlen($post->post_title)>$titleMaxCharSize ? substr($post->post_title,0,$titleMaxCharSize-1) ."..." : $post->post_title);		
            ?>
                <?php if ($count%$posts_per_row==0) { ?>
                    </tr>
                    <tr>
                <?php } ?>
    
                <?php
                    // thumbnail stuff
                    $imgURL = ($vids_are_from_youtube && isset($vid["youtubeID"]) && ($vid["youtubeID"]!="")) 
                                ? "http://img.youtube.com/vi/".$vid["youtubeID"]."/default.jpg"
                                : $post->thumb_URL;
                                
                    if ((isset($post->thumb) && !empty($post->thumb)) || (isset($post->thumb_URL) && !empty($post->thumb_URL))) {
                        $imgSrc = ((isset($post->thumb) && !empty($post->thumb)) 
                                    ? $img_func($post->thumb->ID, array($thumb_width, $thumb_height)) 
                                    : "<img src='".$imgURL."' id='blurb-default-thumbnail-".$post->ID."' width='".$thumb_width."' height='".$thumb_height."' class='blurb-default-thumbnail' />");
                    }
                ?>              
                        <td class="blurb-video-post">
                            <div class="video-post-thumb"><a href="<?php echo get_permalink($post->ID) ?>" title="Read More" class="blurb-image-keyhole blurb-<?php echo $thumb_width ?>x<?php echo $thumb_height ?>-keyhole"><?php echo $imgSrc ?></a></div>
                            <div class="video-post-title"><a href="<?php echo get_permalink($post->ID) ?>" title="Read More"><?php echo apply_filters('the_title', $post_title, $post->ID) ?></a></div>
                          </td>
                <?php $count++; ?>
            <?php } ?>
        	</tr>
            </tbody>
            </table>
		<?php } ?>
        </ul>
    </div>
    
	<?php if (isset($see_more) && !empty($see_more)): ?>
	    <div class="blurb-bottom readMoreD"><a href="<?php echo $see_more ?>">See&nbsp;More</a></div>
    <?php endif; ?>
    
	<div class="blurb-footer">
    </div>
</div>
