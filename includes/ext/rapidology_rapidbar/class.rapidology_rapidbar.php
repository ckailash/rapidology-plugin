<?php

class rapidology_rapidbar
{
	public function __construct(){
		add_action('wp_enqueue_scripts', array($this, 'register_rapidbar_scripts'));
	}

	public static function generate_form_content($optin_id, $page_id, $pagename = '', $details = array())
	{
		if (empty($details)) {
			$all_optins = RAD_Rapidology::get_rapidology_options();
			$details = $all_optins[$optin_id];
		}
		if (isset($_COOKIE['hubspotutk'])) {
			$hubspot_cookie = $_COOKIE['hubspotutk'];
		} else {
			$hubspot_cookie = '';
		}

		//unsetting uneeded array elements
		$details['image_url'] = null;

		$hide_img_mobile_class = isset($details['hide_mobile']) && '1' == $details['hide_mobile'] ? 'rad_rapidology_hide_mobile' : '';
		$image_animation_class = isset($details['image_animation'])
			? esc_attr(' rad_rapidology_image_' . $details['image_animation'])
			: 'rad_rapidology_image_no_animation';
		$image_class = $hide_img_mobile_class . $image_animation_class . ' rad_rapidology_image';

		// Translate all strings if WPML is enabled
		if (function_exists('icl_translate')) {
			$optin_title = '';
			$optin_message = icl_translate('rapidology', 'optin_message_' . $optin_id, $details['optin_message']);
			$email_text = icl_translate('rapidology', 'email_text_' . $optin_id, $details['email_text']);
			$first_name_text = '';
			$single_name_text = '';
			$last_name_text = '';
			$button_text = icl_translate('rapidology', 'button_text_' . $optin_id, $details['button_text']);
			$success_text = icl_translate('rapidology', 'success_message_' . $optin_id, $details['success_message']);
			$footer_text = '';
		} else {
			$optin_title = '';
			$optin_message = $details['optin_message'];
			$email_text = $details['email_text'];
			$first_name_text = '';
			$single_name_text = '';
			$last_name_text = '';
			$button_text = $details['button_text'];
			$success_text = $details['success_message'];
			$footer_text = '';
		}

		$formatted_title = '&lt;h2&gt;&nbsp;&lt;/h2&gt;' != $details['optin_title']
			? str_replace('&nbsp;', '', $optin_title)
			: '';
		$formatted_message = '' != $details['optin_message'] ? $optin_message : '';
		$formatted_footer = '' != $details['footer_text']
			? sprintf(
				'<div class="rad_rapidology_form_footer">
					<p>%1$s</p>
				</div>',
				stripslashes(esc_html($footer_text))
			)
			: '';

		$is_single_name = (isset($details['display_name']) && '1' == $details['display_name']) ? false : true;

		$output = sprintf('
			<div class="rad_rapidology_form_container_wrapper clearfix">
				<div class="rad_rapidology_header_outer">
					<div class="rad_rapidology_form_header%13$s">
						%3$s
						%4$s
					</div>
				</div>
				<div class="rad_rapidology_form_content%6$s%7$s%12$s"%11$s>
					%8$s
					<div class="rad_rapidology_success_container">
						<span class="rad_rapidology_success_checkmark"></span>
					</div>
					<h2 class="rad_rapidology_success_message">%9$s</h2>
					%10$s
				</div>
			</div>
			<span class="rad_rapidology_close_button"></span>',
			('right' == $details['image_orientation'] || 'left' == $details['image_orientation']) && 'widget' !== $details['optin_type']
				? sprintf(' split%1$s', 'right' == $details['image_orientation']
				? ' image_right'
				: '')
				: '',
			(('above' == $details['image_orientation'] || 'right' == $details['image_orientation'] || 'left' == $details['image_orientation']) && 'widget' !== $details['optin_type']) || ('above' == $details['image_orientation_widget'] && 'widget' == $details['optin_type'])
				? sprintf(
				'%1$s',
				empty($details['image_url']['id'])
					? sprintf(
					'<img src="%1$s" alt="%2$s" %3$s>',
					esc_attr($details['image_url']['url']),
					esc_attr(wp_strip_all_tags(html_entity_decode($formatted_title))),
					'' !== $image_class
						? sprintf('class="%1$s"', esc_attr($image_class))
						: ''
				)
					: wp_get_attachment_image($details['image_url']['id'], 'rapidology_image', false, array('class' => $image_class))
			)
				: '',
			('' !== $formatted_message)
				? sprintf(
				'<div class="rad_rapidology_form_text">
						%1$s
					</div>',
				stripslashes(html_entity_decode($formatted_message, ENT_QUOTES, 'UTF-8'))
			)
				: '',
			('below' == $details['image_orientation'] && 'widget' !== $details['optin_type']) || (isset($details['image_orientation_widget']) && 'below' == $details['image_orientation_widget'] && 'widget' == $details['optin_type'])
				? sprintf(
				'%1$s',
				empty($details['image_url']['id'])
					? sprintf(
					'<img src="%1$s" alt="%2$s" %3$s>',
					esc_attr($details['image_url']['url']),
					esc_attr(wp_strip_all_tags(html_entity_decode($formatted_title))),
					'' !== $image_class ? sprintf('class="%1$s"', esc_attr($image_class)) : ''
				)
					: wp_get_attachment_image($details['image_url']['id'], 'rapidology_image', false, array('class' => $image_class))
			)
				: '', //#5
			('no_name' == $details['name_fields'] && !RAD_Rapidology::is_only_name_support($details['email_provider'])) || (RAD_Rapidology::is_only_name_support($details['email_provider']) && $is_single_name)
				? ' rad_rapidology_1_field'
				: sprintf(
				' rad_rapidology_%1$s_fields',
				'first_last_name' == $details['name_fields'] && !RAD_Rapidology::is_only_name_support($details['email_provider'])
					? '3'
					: '2'
			),
			'inline' == $details['field_orientation'] && 'bottom' == $details['form_orientation'] && 'widget' !== $details['optin_type']
				? ' rad_rapidology_bottom_inline'
				: '',
			('stacked' == $details['field_orientation'] && 'bottom' == $details['form_orientation']) || 'widget' == $details['optin_type']
				? ' rad_rapidology_bottom_stacked'
				: '',
			'custom_html' == $details['email_provider']
				? stripslashes(html_entity_decode($details['custom_html']))
				: sprintf('
					%1$s
					<form method="post" class="clearfix">
						%3$s
						<p class="rad_rapidology_rapidbar_input rad_rapidology_subscribe_email">
							<input placeholder="%2$s">
						</p>
						<button data-optin_id="%4$s" data-service="%5$s" data-list_id="%6$s" data-page_id="%7$s" data-post_name="%12$s" data-cookie="%13$s" data-account="%8$s" data-disable_dbl_optin="%11$s" class="rad_rapidology_submit_subscription">
							<span class="rad_rapidology_subscribe_loader"></span>
							<span class="rad_rapidology_button_text rad_rapidology_button_text_color_%10$s">%9$s</span>
						</button>
					</form>',
				'basic_edge' == $details['edge_style'] || '' == $details['edge_style']
					? ''
					: RAD_Rapidology::get_the_edge_code($details['edge_style'], 'widget' == $details['optin_type'] ? 'bottom' : $details['form_orientation']),
				'' != $email_text ? stripslashes(esc_attr($email_text)) : esc_html__('Email', 'rapidology'),
				('no_name' == $details['name_fields'] && !RAD_Rapidology::is_only_name_support($details['email_provider'])) || (RAD_Rapidology::is_only_name_support($details['email_provider']) && $is_single_name)
					? ''
					: sprintf(
					'<p class="rad_rapidology_rapidbar_input rad_rapidology_subscribe_name">
								<input placeholder="%1$s%2$s" maxlength="50">
							</p>%3$s',
					'first_last_name' == $details['name_fields']
						? sprintf(
						'%1$s',
						'' != $first_name_text
							? stripslashes(esc_attr($first_name_text))
							: esc_html__('First Name', 'rapidology')
					)
						: '',
					('first_last_name' != $details['name_fields'])
						? sprintf('%1$s', '' != $single_name_text
						? stripslashes(esc_attr($single_name_text))
						: esc_html__('Name', 'rapidology')) : '',
					'first_last_name' == $details['name_fields'] && !RAD_Rapidology::is_only_name_support($details['email_provider'])
						? sprintf('
									<p class="rad_rapidology_rapidbar_input rad_rapidology_subscribe_last">
										<input placeholder="%1$s" maxlength="50">
									</p>',
						'' != $last_name_text ? stripslashes(esc_attr($last_name_text)) : esc_html__('Last Name', 'rapidology')
					)
						: ''
				),
				esc_attr($optin_id),
				esc_attr($details['email_provider']), //#5
				esc_attr($details['email_list']),
				esc_attr($page_id),
				esc_attr($details['account_name']),
				'' != $button_text ? stripslashes(esc_html($button_text)) : esc_html__('SUBSCRIBE!', 'rapidology'),
				isset($details['button_text_color']) ? esc_attr($details['button_text_color']) : '', // #10
				isset($details['disable_dbl_optin']) && '1' === $details['disable_dbl_optin'] ? 'disable' : '',#11
				esc_attr($pagename),#12
				esc_attr($hubspot_cookie)#13

			),
			'' != $success_text
				? stripslashes(esc_html($success_text))
				: esc_html__('You have Successfully Subscribed!', 'rapidology'), //#10
			$formatted_footer,
			'custom_html' == $details['email_provider']
				? sprintf(
				' data-optin_id="%1$s" data-service="%2$s" data-list_id="%3$s" data-page_id="%4$s" data-account="%5$s"',
				esc_attr($optin_id),
				'custom_form',
				'custom_form',
				esc_attr($page_id),
				'custom_form'
			)
				: '',
			'custom_html' == $details['email_provider'] ? ' rad_rapidology_custom_html_form' : '',
			isset($details['header_text_color'])
				? sprintf(
				' rad_rapidology_header_text_%1$s',
				esc_attr($details['header_text_color'])
			)
				: ' rad_rapidology_header_text_dark' //#14
		);

		return $output;
	}

	public static function generate_custom_css($form_class, $single_optin = array())
	{
		$font_functions = RAD_Rapidology::load_fonts_class();
		$custom_css = '';

		if (isset($single_optin['form_bg_color']) && '' !== $single_optin['form_bg_color']) {
			$custom_css .= $form_class . ' .rad_rapidology_rapidbar_container, .rad_rapidology_form_container_wrapper, .rad_rapidology_form_text { background-color: ' . $single_optin['form_bg_color'] . ' !important; } ';

			if ('zigzag_edge' === $single_optin['edge_style']) {
				$custom_css .=
					$form_class . ' .zigzag_edge .rad_rapidology_form_content:before { background: linear-gradient(45deg, transparent 33.33%, ' . $single_optin['form_bg_color'] . ' 33.333%, ' . $single_optin['form_bg_color'] . ' 66.66%, transparent 66.66%), linear-gradient(-45deg, transparent 33.33%, ' . $single_optin['form_bg_color'] . ' 33.33%, ' . $single_optin['form_bg_color'] . ' 66.66%, transparent 66.66%) !important; background-size: 20px 40px !important; } ' .
					$form_class . ' .zigzag_edge.rad_rapidology_form_right .rad_rapidology_form_content:before, ' . $form_class . ' .zigzag_edge.rad_rapidology_form_left .rad_rapidology_form_content:before { background-size: 40px 20px !important; }
					@media only screen and ( max-width: 767px ) {' .
					$form_class . ' .zigzag_edge.rad_rapidology_form_right .rad_rapidology_form_content:before, ' . $form_class . ' .zigzag_edge.rad_rapidology_form_left .rad_rapidology_form_content:before { background: linear-gradient(45deg, transparent 33.33%, ' . $single_optin['form_bg_color'] . ' 33.333%, ' . $single_optin['form_bg_color'] . ' 66.66%, transparent 66.66%), linear-gradient(-45deg, transparent 33.33%, ' . $single_optin['form_bg_color'] . ' 33.33%, ' . $single_optin['form_bg_color'] . ' 66.66%, transparent 66.66%) !important; background-size: 20px 40px !important; } ' .
					'}';
			}
		}

		if (isset($single_optin['header_bg_color']) && '' !== $single_optin['header_bg_color']) {
			$custom_css .= $form_class . ' .rad_rapidology_form_container .rad_rapidology_form_header { background-color: ' . $single_optin['header_bg_color'] . ' !important; } ';

			switch ($single_optin['edge_style']) {
				case 'curve_edge' :
					$custom_css .= $form_class . ' .curve_edge .curve { fill: ' . $single_optin['header_bg_color'] . '} ';
					break;

				case 'wedge_edge' :
					$custom_css .= $form_class . ' .wedge_edge .triangle { fill: ' . $single_optin['header_bg_color'] . '} ';
					break;

				case 'carrot_edge' :
					$custom_css .=
						$form_class . ' .carrot_edge .rad_rapidology_form_content:before { border-top-color: ' . $single_optin['header_bg_color'] . ' !important; } ' .
						$form_class . ' .carrot_edge.rad_rapidology_form_right .rad_rapidology_form_content:before, ' . $form_class . ' .carrot_edge.rad_rapidology_form_left .rad_rapidology_form_content:before { border-top-color: transparent !important; border-left-color: ' . $single_optin['header_bg_color'] . ' !important; }
						@media only screen and ( max-width: 767px ) {' .
						$form_class . ' .carrot_edge.rad_rapidology_form_right .rad_rapidology_form_content:before, ' . $form_class . ' .carrot_edge.rad_rapidology_form_left .rad_rapidology_form_content:before { border-top-color: ' . $single_optin['header_bg_color'] . ' !important; border-left-color: transparent !important; }
						}';
					break;
			}

			if ('dashed' === $single_optin['border_style']) {
				if ('breakout_edge' !== $single_optin['edge_style']) {
					$custom_css .= $form_class . ' .rad_rapidology_form_container { background-color: ' . $single_optin['header_bg_color'] . ' !important; } ';
				} else {
					$custom_css .= $form_class . ' .rad_rapidology_header_outer { background-color: ' . $single_optin['header_bg_color'] . ' !important; } ';
				}
			}
		}

		if (isset($single_optin['form_button_color']) && '' !== $single_optin['form_button_color']) {
			$custom_css .= $form_class . ' .rad_rapidology_form_content button { background-color: ' . $single_optin['form_button_color'] . ' !important; } ';
		}

		if (isset($single_optin['border_color']) && '' !== $single_optin['border_color'] && 'no_border' !== $single_optin['border_orientation']) {
			if ('breakout_edge' === $single_optin['edge_style']) {
				switch ($single_optin['border_style']) {
					case 'letter' :
						$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_letter .rad_rapidology_header_outer { background: repeating-linear-gradient( 135deg, ' . $single_optin['border_color'] . ', ' . $single_optin['border_color'] . ' 10px, #fff 10px, #fff 20px, #f84d3b 20px, #f84d3b 30px, #fff 30px, #fff 40px ) !important; } ';
						break;

					case 'double' :
						$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_double .rad_rapidology_form_header { -moz-box-shadow: inset 0 0 0 6px ' . $single_optin['header_bg_color'] . ', inset 0 0 0 8px ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 0 0 6px ' . $single_optin['header_bg_color'] . ', inset 0 0 0 8px ' . $single_optin['border_color'] . '; box-shadow: inset 0 0 0 6px ' . $single_optin['header_bg_color'] . ', inset 0 0 0 8px ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';

						switch ($single_optin['border_orientation']) {
							case 'top' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_double.rad_rapidology_border_position_top .rad_rapidology_form_header { -moz-box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'right' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_double.rad_rapidology_border_position_right .rad_rapidology_form_header { -moz-box-shadow: inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'bottom' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_double.rad_rapidology_border_position_bottom .rad_rapidology_form_header { -moz-box-shadow: inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'left' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_double.rad_rapidology_border_position_left .rad_rapidology_form_header { -moz-box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'top_bottom' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_double.rad_rapidology_border_position_top_bottom .rad_rapidology_form_header { -moz-box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . ', inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . ', inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . ', inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'left_right' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_double.rad_rapidology_border_position_left_right .rad_rapidology_form_header { -moz-box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . ', inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . ', inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . ', inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
						}
						break;

					case 'inset' :
						$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_inset .rad_rapidology_form_header { -moz-box-shadow: inset 0 0 0 3px ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 0 0 3px ' . $single_optin['border_color'] . '; box-shadow: inset 0 0 0 3px ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';

						switch ($single_optin['border_orientation']) {
							case 'top' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_inset.rad_rapidology_border_position_top .rad_rapidology_form_header { -moz-box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'right' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_inset.rad_rapidology_border_position_right .rad_rapidology_form_header { -moz-box-shadow: inset -3px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset -3px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset -3px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'bottom' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_inset.rad_rapidology_border_position_bottom .rad_rapidology_form_header { -moz-box-shadow: inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'left' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_inset.rad_rapidology_border_position_left .rad_rapidology_form_header { -moz-box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'top_bottom' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_inset.rad_rapidology_border_position_top_bottom .rad_rapidology_form_header { -moz-box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . ', inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . ', inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . ', inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'left_right' :
								$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_inset.rad_rapidology_border_position_left_right .rad_rapidology_form_header { -moz-box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . ', inset -3px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . ', inset -3px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . ', inset -3px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
						}
						break;

					case 'solid' :
						$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_solid .rad_rapidology_form_header { border-color: ' . $single_optin['border_color'] . ' !important } ';
						break;

					case 'dashed' :
						$custom_css .= $form_class . ' .breakout_edge.rad_rapidology_border_dashed .rad_rapidology_form_header { border-color: ' . $single_optin['border_color'] . ' !important } ';
						break;
				}
			} else {
				switch ($single_optin['border_style']) {
					case 'letter' :
						$custom_css .= $form_class . ' .rad_rapidology_border_letter { background: repeating-linear-gradient( 135deg, ' . $single_optin['border_color'] . ', ' . $single_optin['border_color'] . ' 10px, #fff 10px, #fff 20px, #f84d3b 20px, #f84d3b 30px, #fff 30px, #fff 40px ) !important; } ';
						break;

					case 'double' :
						$custom_css .= $form_class . ' .rad_rapidology_border_double { -moz-box-shadow: inset 0 0 0 6px ' . $single_optin['header_bg_color'] . ', inset 0 0 0 8px ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 0 0 6px ' . $single_optin['header_bg_color'] . ', inset 0 0 0 8px ' . $single_optin['border_color'] . '; box-shadow: inset 0 0 0 6px ' . $single_optin['header_bg_color'] . ', inset 0 0 0 8px ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';

						switch ($single_optin['border_orientation']) {
							case 'top' :
								$custom_css .= $form_class . ' .rad_rapidology_border_double.rad_rapidology_border_position_top { -moz-box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'right' :
								$custom_css .= $form_class . ' .rad_rapidology_border_double.rad_rapidology_border_position_right { -moz-box-shadow: inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'bottom' :
								$custom_css .= $form_class . ' .rad_rapidology_border_double.rad_rapidology_border_position_bottom { -moz-box-shadow: inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'left' :
								$custom_css .= $form_class . ' .rad_rapidology_border_double.rad_rapidology_border_position_left { -moz-box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'top_bottom' :
								$custom_css .= $form_class . ' .rad_rapidology_border_double.rad_rapidology_border_position_top_bottom { -moz-box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . ', inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . ', inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 8px 0 0 ' . $single_optin['border_color'] . ', inset 0 -6px 0 0 ' . $single_optin['header_bg_color'] . ', inset 0 -8px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
								break;

							case 'left_right' :
								$custom_css .= $form_class . ' .rad_rapidology_border_double.rad_rapidology_border_position_left_right { -moz-box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . ', inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . ', inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset 8px 0 0 0 ' . $single_optin['border_color'] . ', inset -6px 0 0 0 ' . $single_optin['header_bg_color'] . ', inset -8px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['border_color'] . '; } ';
						}
						break;

					case 'inset' :
						$custom_css .= $form_class . ' .rad_rapidology_border_inset { -moz-box-shadow: inset 0 0 0 3px ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 0 0 3px ' . $single_optin['border_color'] . '; box-shadow: inset 0 0 0 3px ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';

						switch ($single_optin['border_orientation']) {
							case 'top' :
								$custom_css .= $form_class . ' .rad_rapidology_border_inset.rad_rapidology_border_position_top { -moz-box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'right' :
								$custom_css .= $form_class . ' .rad_rapidology_border_inset.rad_rapidology_border_position_right { -moz-box-shadow: inset -3px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset -3px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset -3px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'bottom' :
								$custom_css .= $form_class . ' .rad_rapidology_border_inset.rad_rapidology_border_position_bottom { -moz-box-shadow: inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'left' :
								$custom_css .= $form_class . ' .rad_rapidology_border_inset.rad_rapidology_border_position_left { -moz-box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'top_bottom' :
								$custom_css .= $form_class . ' .rad_rapidology_border_inset.rad_rapidology_border_position_top_bottom { -moz-box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . ', inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . ', inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 0 3px 0 0 ' . $single_optin['border_color'] . ', inset 0 -3px 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
								break;

							case 'left_right' :
								$custom_css .= $form_class . ' .rad_rapidology_border_inset.rad_rapidology_border_position_left_right { -moz-box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . ', inset -3px 0 0 0 ' . $single_optin['border_color'] . '; -webkit-box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . ', inset -3px 0 0 0 ' . $single_optin['border_color'] . '; box-shadow: inset 3px 0 0 0 ' . $single_optin['border_color'] . ', inset -3px 0 0 0 ' . $single_optin['border_color'] . '; border-color: ' . $single_optin['header_bg_color'] . '; } ';
						}
						break;

					case 'solid' :
						$custom_css .= $form_class . ' .rad_rapidology_border_solid { border-color: ' . $single_optin['border_color'] . ' !important } ';
						break;

					case 'dashed' :
						$custom_css .= $form_class . ' .rad_rapidology_border_dashed .rad_rapidology_form_container_wrapper { border-color: ' . $single_optin['border_color'] . ' !important } ';
						break;
				}
			}
		}

		$custom_css .= isset($single_optin['form_button_color']) && '' !== $single_optin['form_button_color'] ? $form_class . ' .rad_rapidology_form_content button { background-color: ' . $single_optin['form_button_color'] . ' !important; } ' : '';
		$custom_css .= isset($single_optin['header_font']) ? $font_functions->et_gf_attach_font($single_optin['header_font'], $form_class . ' h2, ' . $form_class . ' h2 span, ' . $form_class . ' h2 strong') : '';
		$custom_css .= isset($single_optin['body_font']) ? $font_functions->et_gf_attach_font($single_optin['body_font'], $form_class . ' p, ' . $form_class . ' p span, ' . $form_class . ' p strong, ' . $form_class . ' form input, ' . $form_class . ' form button span') : '';

		$custom_css .= isset($single_optin['custom_css']) ? ' ' . $single_optin['custom_css'] : '';

		return $custom_css;

	}


	public function register_rapidbar_scripts(){
		if(is_admin_bar_showing()){
			$admin_bar = true;
		}else{
			$admin_bar = false;
		}
		$script_locals = array(
			'admin_bar' => $admin_bar
		);

		wp_register_script('rapidbar_js', RAD_RAPIDOLOGY_PLUGIN_URI.  '/includes/ext/rapidology_rapidbar/js/rapidbar.js', array( 'jquery' ), '1.0', true);
		wp_localize_script('rapidbar_js', 'rapidbar', $script_locals);
		wp_enqueue_script('rapidbar_js');
	}
}

$rapidbar = new rapidology_rapidbar();
