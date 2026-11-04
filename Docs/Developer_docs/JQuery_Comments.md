# JQuery Comments for CommentsFeed widget

`CommentsFeed` displays comments using the local jQuery Comments plugin. It uses one
layout column by default; set `width` to override this. Its default height grows
with the comments; an explicit height makes the feed scrollable.

Configure the comment attributes on the widget or its object's `CommentBehavior`.
For a feed in a record editor, add a scalar relation filter pointing to the parent
record. Its value scopes the loaded comments and attaches new comments to that
record. Adding comments is disabled until the parent record has been saved.

Comments are created, edited and deleted immediately, independently of the editor's
Save button. The feed uses the comments plugin's own loading and saving indicators.
Comment actions broadcast their effects normally. Other widgets decide whether to
refresh and may show their own busy indicators, independently of the feed.
The comment composer has padding around its user icon and input. Replies, voting,
mentions and attachments are not supported.

Comments display as plain text by default. Set `"enable_markdown": true` on the
`CommentsFeed` widget to display Markdown using Markian's lightweight viewer and the
configured `LIBS.MARKIAN.MARKDOWN.CSS` stylesheet and CSS classes. Ordinary line
breaks remain visible. Raw HTML is not trusted, and unsafe links are not activated.
Code fences display as code without syntax highlighting or Mermaid rendering.
The composer and edit fields continue to work with Markdown source; rendering
does not change saved text. Edited timestamps remain outside the viewer.
The shared Markian adapter releases viewers when comments are replaced or removed.
With Markdown disabled, the feed does not load Markian assets or install its formatter.

After typing a comment, press Tab to focus Send, then Enter or Space to submit it.
The same keyboard controls work with Save and Delete when editing a comment.
Send and Save use the current EasyUI template's promoted button style; Delete uses
its regular transparent style. All three use the template's focus outline.
The Edit controls below comments use native EasyUI plain buttons, including the
template's hover background, border and keyboard-focus outline.
New-comment and edit inputs use the template's standard input-focus color on
their borders and visible speech-bubble tips while focused.

Set `comment_avatar_url_attribute` on the widget or its object's `CommentBehavior`
to an attribute containing each author's avatar URL, for example
`"comment_avatar_url_attribute": "CREATED_BY__AVATAR_URL"`. An explicit widget
setting overrides the behavior's default. Without either setting, avatars are
hidden in existing comments and their reserved space is removed.
The new-comment composer shows its avatar (a default person icon when no image is
available) at 50% opacity in its compact layout, without a Send button.
The expanded layout hides the avatar while Send is visible, even after focus
moves elsewhere. Closing the composer restores the compact layout and its avatar.
The input's speech-bubble tip remains visible in both states.