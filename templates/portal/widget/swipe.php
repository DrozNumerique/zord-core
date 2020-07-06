				<div id="<?php echo $models['id']; ?>" class="swipe" data-index="<?php echo $models['index'] ?? '0'; ?>" data-frames="<?php echo $models['selector'] ?? 'li.frame'; ?>" data-direction="<?php echo $models['direction'] ?? 'vertical'; ?>" data-transition="<?php echo $models['transition'] ?? 'position'; ?>" data-interval="<?php echo $models['interval'] ?? '0'; ?>">
					<div class="top controls"></div>
					<div class="bottom controls"></div>
					<div class="left controls"></div>
					<div class="right controls"></div>
					<div class="backward controls" data-direction="backward"><span></span></div>
					<div class="forward controls" data-direction="forward"><span></span></div>
					<div class="window">
						<div class="slider">
<?php $this->render($models['frames']); ?>
						</div>
					</div>
				</div>
