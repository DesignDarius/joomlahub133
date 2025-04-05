<?php
/**
 ------------------------------------------------------------------------
 SOLIDRES - Accommodation booking extension for Joomla
 ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 ------------------------------------------------------------------------
 */

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/com_solidres/apartment/default_feedback.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.12.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

if (isset($this->property->feedbacks)): ?>
	<div class="sr-apartment-review" id="sr-apartment-review">
		<h2 class="leader"><?php echo Text::_('SR_REVIEWS'); ?></h2>
		<div class="<?php echo SR_UI_GRID_CONTAINER; ?>">
			<div class="<?php echo SR_UI_GRID_COL_3; ?>">
				<div class="sr-review sr-flex-box">
					<header><?php echo $this->property->reviewScores . '/' . $this->property->feedbackMaxScore; ?></header>
					<div>
						(<?php echo Text::plural('SR_FEEDBACK_REVIEW_COUNT', $this->property->reviewCount); ?>)
					</div>
				</div>
			</div>
			<div class="<?php echo SR_UI_GRID_COL_9; ?>">
				<ul>
					<?php
					foreach ($this->property->reviewFields as $name => $score):
						$percentage = ($score * 100) / $this->property->feedbackMaxScore;
						?>
						<li>
							<div class="<?php echo SR_UI_GRID_CONTAINER; ?>">
								<div class="<?php echo SR_UI_GRID_COL_4; ?>">
									<?php echo $name; ?>
								</div>
								<div class="<?php echo SR_UI_GRID_COL_8; ?>">
									<div class="progress progress-<?php
									echo $percentage > 79 ? 'success' :
										($percentage < 50 ? 'warning' : 'info');
									?>">
										<div class="progress-bar bar"
										     style="width: <?php echo $percentage . '%'; ?>">
											<strong><?php echo number_format($score, 1); ?></strong>
										</div>
									</div>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<hr/>
		<?php echo $this->property->feedbacks->render; ?>
	</div>
<?php endif; ?>