<?php
/* This is the interface the contest handler classes need to implement. It defines a handler method for every relevant
 * message type.
 */

interface ContestHandler {
	/* this function handles incoming impression messages. it is responsible for posting the data back to the contest server
	 * using ContestMessage::postTo('stdout').
	 *
	 * @param ContestImpression $msg the incoming impression, use the provided accessor methods such as getClient() and getItem() to access the contained data
	 */
	function handleImpression(ContestImpression $msg);

	/* this function handles incoming feedback messages.
	 *
	 * @param ContestFeedback $msg the incoming feedback, use accessor methods such as getClient(), getSourceItem() and getTargetItem() to access the contained data
	 */
	function handleFeedback(ContestFeedback $msg);

	/* this function is invoked when an error is received from the contest server.
	 *
	 * @param ContestError $error the error message object that was sent by the server, access the contained message through getMessage().
	 */
	function handleError(ContestError $error);

	/* this function simply returns an instance of the ContestHandler to be used to handle the received contest messages.
	 *
	 * @return ContestHandler the handler object to be used.
	 */
	static function getInstance();
}
