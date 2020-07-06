				<div id="<?php echo $models['id']; ?>" class="swipe" data-index="0" data-frames="<?php echo $models['selector']; ?>" data-direction="<?php echo $models['direction'] ?? 'vertical'; ?>" data-transition="<?php echo $models['transition'] ?? 'position'; ?>" data-interval="<?php echo $models['interval'] ?? '0'; ?>">
					<div class="top controls">
<?php $this->render($models['top'] ?? 'controls'); ?>
					</div>
					<div class="bottom controls">
<?php $this->render($models['bottom'] ?? 'controls'); ?>
					</div>
					<div class="left controls">
<?php $this->render($models['left'] ?? 'controls'); ?>
					</div>
					<div class="right controls">
<?php $this->render($models['right'] ?? 'controls'); ?>
					</div>
					<div class="backward controls" data-direction="backward">
<?php $this->render($models['backward'] ?? 'controls'); ?>
					</div>
					<div class="forward controls" data-direction="forward">
<?php $this->render($models['forward'] ?? 'controls'); ?>
					</div>
					<div class="window">
						<div class="slider">
<?php $this->render($models['frames']); ?>
						</div>
					</div>
				</div>
