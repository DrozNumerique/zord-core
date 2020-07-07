				<div id="<?php echo $models['id']; ?>" class="slide" 
				     data-index="<?php echo $models['index'] ?? Zord::value('portal', ['slide','index']); ?>"
				     data-frames="<?php echo $models['selector'] ?? Zord::value('portal', ['slide','selector']); ?>"
				     data-direction="<?php echo $models['direction'] ?? Zord::value('portal', ['slide','direction']); ?>"
				     data-transition="<?php echo $models['transition'] ?? Zord::value('portal', ['slide','transition']); ?>"
				     data-interval="<?php echo $models['interval'] ?? Zord::value('portal', ['slide','interval']); ?>"
				     data-controls='<?php echo Zord::json_encode(Zord::array_merge(Zord::value('portal', ['slide','controls']), $models['controls'] ?? []), false); ?>'>
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
