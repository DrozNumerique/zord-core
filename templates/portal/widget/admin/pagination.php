                    <ul id="pagination" data-tab="<?php echo $models['current'] ?>" data-count="<?php echo $models['count']; ?>" data-limit="<?php echo $models['limit']; ?>" data-offset="<?php echo $models['offset']; ?>">
                    	<li class="cursor previous">
                    		<span><?php echo $locale->tab->users->previous; ?></span>
                    	</li>
                    	<li class="index">
                    		<select>
<?php   for ($offset = 0 ; $offset < $models['count'] ; $offset += $models['limit']) { ?>
								<option value="<?php echo $offset; ?>"<?php echo $offset == $models['offset'] ? ' selected' : ''; ?>><?php echo $offset + 1; ?> - <?php echo min([$offset + $models['limit'], $models['count']]); ?></option>
<?php   } ?>
							</select>
						</li>
                    	<li class="cursor next">
                    		<span><?php echo $locale->tab->users->next; ?></span>
                    	</li>
                    </ul>
