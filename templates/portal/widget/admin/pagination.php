<?php $locale = Zord::getLocale('admin', $lang); ?>
                    <ul id="pagination" data-count="<?php echo $models['count']; ?>" data-limit="<?php echo $models['limit']; ?>" data-offset="<?php echo $models['offset']; ?>">
                    	<li class="cursor previous">
                    		<span><?php echo $locale->pagination->previous; ?></span>
                    	</li>
                    	<li class="index">
                    		<select>
<?php   for ($offset = 0 ; $offset < $models['count'] ; $offset += $models['limit']) { ?>
								<option value="<?php echo $offset; ?>"<?php echo $offset == $models['offset'] ? ' selected' : ''; ?>><?php echo $models['index'][$offset]; ?> => <?php echo $models['index'][min([$offset + $models['limit'], $models['count']]) - 1]; ?></option>
<?php   } ?>
							</select>
						</li>
                    	<li class="cursor next">
                    		<span><?php echo $locale->pagination->next; ?></span>
                    	</li>
                    </ul>