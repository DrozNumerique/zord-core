				<div id="<?php echo $models['id']; ?>" class="swipe" data-index="0" data-frames="<?php echo $models['selector']; ?>" data-transition="<?php echo $models['transition'] ?? 'crossfade'; ?>" data-interval="<?php echo $models['interval'] ?? '0'; ?>">
					<div class="backward" data-direction="backward">
						<span><?php echo $models['backward'] ?? ''; ?></span>
					</div>
					<div class="window">
						<div class="slider">
<?php $this->render($models['frames']); ?>
						</div>
					</div>
					<div class="forward" data-direction="forward">
						<span><?php echo $models['forward'] ?? ''; ?></span>
					</div>
				</div>
