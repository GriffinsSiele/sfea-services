# src.socket_params.interfaces package

## Submodules

## src.socket_params.interfaces.socket module

## src.socket_params.interfaces.socket_messages module

### *class* src.socket_params.interfaces.socket_messages.MESSAGE

Базовые классы: `object`

#### ACTIVE *= 1*

#### USER_AGENT *= 6*

#### SETTINGS *= 19*

#### TOKEN *= 23*

#### USER_CONTACTS *= 32*

#### FETCH_CHATS *= 48*

#### FETCH_CHATS_MESSAGES *= 49*

#### MARK_AS_READ *= 50*

#### PICK_ACTION_BUTTON *= 64*

#### ACTION_TYPE *= 65*

#### ACTION_CHAT_TYPING *= 177*

### *class* src.socket_params.interfaces.socket_messages.SocketMessages

Базовые классы: `object`

#### *static* dict_messages()

#### *static* payload(opcode, \*args)

#### *static* now()

#### *static* send_token(token)

#### *static* send_user_agent()

#### *static* send_active()

#### *static* send_settings(token)

#### *static* send_user_contacts(group_id, contact_type)

#### *static* send_fetch_chats_messages(chat_id)

#### *static* send_fetch_chats(chat_id)

#### *static* send_mark_as_read(chat_id, profile_id, last_message_id)

#### *static* send_action_type(chat_id)

#### *static* send_action_chat_typing(chat_id)

#### *static* send_pick_action_button(chat_id, text)

## Module contents
