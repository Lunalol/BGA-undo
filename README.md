Just import it as a trait in your main Game.php file:

	use undo;

First, don't forget to enable BGA native undo support in "gameinfos.inc.php" file:

  'db_undo_support' => true,

For your 'getArgs' function, you can use boolean function 'checkUndo()' to display or not undo button in the UI.

To clear undo stack and store a new undo state, use 'undoSavepoint()' as usual.
You must use that function at start of a player turn and when a not undoable action is done (roll dice, draw cards...).

As soon active player make an action, use 'undoUpdate()' to add one more undo state to the stack.
You must use that function only for undoable actions.

Use 'undoRestorePoint()' to go back one state.
Use 'undoRestorePoint(true)' to go back to first state.

Please give feedbacks !
Have some good games.
Lunalol.
