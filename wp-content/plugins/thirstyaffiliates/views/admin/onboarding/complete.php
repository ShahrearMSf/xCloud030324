<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<?php
use ThirstyAffiliates\Helpers\Onboarding_Helper;
?>
<h2 class="ta-wizard-finished"><?php esc_html_e("That’s It! You're Ready to Roll", 'thirstyaffiliates'); ?></h2>
<div id="ta-wizard-completed">
  <div id="ta-wizard-content-section"><?php echo Onboarding_Helper::get_completed_step_urls_html(); ?></div>

  <h2 class="ta-wizard-step-title"><?php esc_html_e('Your next step...', 'thirstyaffiliates'); ?></h2>
  <div class="ta-wizard-selected-content ta-wizard-selected-content-full-scape">
    <div class="ta-wizard-selected-content-column">
        <div class="ta-wizard-selected-content-image-box">
          <div class="ta-wizard-selected-content-image-thumbnail">
            <img src="<?php echo $this->_constants->IMAGES_ROOT_URL(); ?>onboarding/how-to-use-thirstyaffiliates.jpg" alt="<?php esc_html_e('Getting Started with ThirstyAffiliates','thirstyaffiliates'); ?>" />
          </div>
          <div class="ta-wizard-selected-content-image-description">
             <a href="https://thirstyaffiliates.com/knowledge-base/" target="_blank">
              <h4 class="ta-image-title"><?php esc_html_e('Getting Started with ThirstyAffiliates','thirstyaffiliates'); ?></h4>
              <p class="ta-image-desc"><?php esc_html_e('Now that you\'ve configured ThirstyAffiliates, it\'s time to learn the basics of the plugin so you can start monetizing your content!','thirstyaffiliates'); ?></p>
            </a>
          </div>
        </div>
    </div>
  </div>

  <h2 class="ta-wizard-step-title"><?php esc_html_e('Make the most of your ThirstyAffiliates site...', 'thirstyaffiliates'); ?></h2>
  <div class="ta-wizard-selected-content ta-wizard-selected-content-full-scape">
    <div class="ta-wizard-selected-content-column">
        <div class="ta-wizard-selected-content-image-box">
          <div class="ta-wizard-selected-content-image-thumbnail">
            <img src="<?php echo $this->_constants->IMAGES_ROOT_URL(); ?>onboarding/thirstyaffiliates-blog-screenshot.jpg" alt="<?php esc_html_e('ThirstyAffiliates Blog','thirstyaffiliates'); ?>" />
          </div>
          <div class="ta-wizard-selected-content-image-description">
            <a href="https://thirstyaffiliates.com/blog/" target="_blank">
              <h4 class="ta-image-title"><?php esc_html_e('ThirstyAffiliates Blog','thirstyaffiliates'); ?></h4>
              <p class="ta-image-desc"><?php esc_html_e('Sign up for tips, tricks, and industry updates from top affiliate marketers and influencers.','thirstyaffiliates'); ?></p>
            </a>
          </div>
        </div>
    </div>
  </div>
</div>
