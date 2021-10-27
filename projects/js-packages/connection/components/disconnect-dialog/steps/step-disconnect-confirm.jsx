/**
 * External Dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal Dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { DecorativeCard } from '@automattic/jetpack-components';
import disconnectImage from '../images/disconnect-confirm.jpg';

/**
 * Shows the step that confirms the site has been disconnected, asks if user would like to provide feedback.
 *
 * @param {object} props - The properties.
 * @returns {React.Component} - StepDisconnectConfirm Component
 */
const StepDisconnectConfirm = props => {
	const { onExit, onProvideFeedback, assetBaseUrl } = props;

	return (
		<div className="jp-connection__disconnect-dialog__content">
			<DecorativeCard icon="unlink" imageUrl={ assetBaseUrl + disconnectImage } />

			<div className="jp-connection__disconnect-dialog__step-copy jp-connection__disconnect-dialog__step-copy--narrow">
				<h1>
					{ createInterpolateElement(
						__( 'Jetpack has been <br/>successfully disconnected.', 'jetpack' ),
						{
							br: <br />,
						}
					) }
				</h1>

				<p>
					{ __(
						'We’re sorry to see you go. Here at Jetpack, we’re always striving to provide the best experience for our customers. Please take our short survey (2 minutes, promise).',
						'jetpack'
					) }
				</p>
				<p>
					<Button
						isPrimary
						onClick={ onProvideFeedback }
						className="jp-connection__disconnect-dialog__btn-back-to-wp"
					>
						{ __( 'Help us improve', 'jetpack' ) }
					</Button>
				</p>
				<a
					className="jp-connection__disconnect-dialog__link jp-connection__disconnect-dialog__link--bold"
					href="#"
					onClick={ onExit }
				>
					{ __( 'No thank you', 'jetpack' ) }
				</a>
			</div>
		</div>
	);
};

StepDisconnectConfirm.PropTypes = {
	/** Callback used to close the modal. */
	onExit: PropTypes.func,
	/** Callback used to change the state if user would like to provide feedback. */
	onProvideFeedback: PropTypes.func,
	/** Base URL for where webpack-ed images will be stored for the consumer of this component. */
	assetBaseUrl: PropTypes.string,
};

export default StepDisconnectConfirm;
