				<div id="<?php echo $models['id']; ?>" class="<?php echo implode(' ', array_merge(['slide'], $models['class'] ?? [])); ?>" 
				     data-index="<?php echo $models['index'] ?? Zord::value('portal', ['slides','default','index']); ?>"
				     data-frames="<?php echo $models['selector'] ?? Zord::value('portal', ['slides','default','selector']); ?>"
				     data-direction="<?php echo $models['direction'] ?? Zord::value('portal', ['slides','default','direction']); ?>"
				     data-transition="<?php echo $models['transition'] ?? Zord::value('portal', ['slides','default','transition']); ?>"
				     data-interval="<?php echo $models['interval'] ?? Zord::value('portal', ['slides','default','interval']); ?>"
				     data-limits="<?php echo $models['limits'] ?? Zord::value('portal', ['slides','default','limits']); ?>"
				     data-step="<?php echo $models['step'] ?? Zord::value('portal', ['slides','default','step']); ?>"
				     data-controls='<?php echo Zord::json_encode(Zord::array_merge(Zord::value('portal', ['slides','default','controls']), $models['controls'] ?? []), false); ?>'>
					<div class="top controls"></div>
					<div class="bottom controls"></div>
					<div class="left controls"></div>
					<div class="right controls"></div>
					<div class="backward controls" data-direction="backward"><span></span></div>
					<div class="forward controls" data-direction="forward"><span></span></div>
					<div class="window">
						<div class="slider">
<?php $this->render($models['frames'], $models); ?>
						</div>
					</div>
				</div>
