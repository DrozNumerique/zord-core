				<div id="<?php echo $models['id']; ?>" class="swipe" data-index="0" data-frames="<?php echo $models['selector']; ?>" data-direction="<?php echo $models['direction']; ?>">
					<div class="backward">
						<span><?php echo $models['backward'] ?? ''; ?></span>
					</div>
					<div class="window">
						<div class="slider">
<?php $this->render($models['frames']); ?>
						</div>
					</div>
					<div class="forward">
						<span><?php echo $models['forward'] ?? ''; ?></span>
					</div>
				</div>
