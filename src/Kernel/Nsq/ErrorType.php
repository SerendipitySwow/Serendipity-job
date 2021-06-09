<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

/**
 * @psalm-immutable
 */
final class ErrorType
{
    /**
     * A generic error type without any more hints.
     */
    public const E_INVALID = true;
    /**
     * This error might be returned during multiple occasions. It can be returned for IDENTIFY, AUTH or MPUB messages.
     * It is caused for payloads that do not meet certain requirements. For IDENTIFY and AUTH, this is usually a bug in
     * the library and should be reported. For MPUB, this error can occur if the payload is larger than the maximum
     * payload size specified in the nsqd config.
     */
    public const E_BAD_BODY = true;
    /**
     * This error indicates that the topic sent to nsqd is not valid.
     */
    public const E_BAD_TOPIC = true;
    /**
     * This error indicates that the channel sent to nsqd is not valid.
     */
    public const E_BAD_CHANNEL = true;
    /**
     * This error is returned by nsqd if the message in the payload of a publishing operation does not meet the
     * requirements of the server. This might be caused by too big payloads being sent to nsqd. You should consider
     * adding a limit to the payload size or increasing it in the nsqd config.
     */
    public const E_BAD_MESSAGE = true;
    /**
     * This error may happen if a error condition is met after validating the input on the nsqd side. This is usually a
     * temporary error and can be caused by topics being added, deleted or cleared.
     */
    public const E_PUB_FAILED = true;
    /**
     * This error may happen if a error condition is met after validating the input on the nsqd side. This is usually a
     * temporary error and can be caused by topics being added, deleted or cleared.
     */
    public const E_MPUB_FAILED = true;
    /**
     * This error may happen if a error condition is met after validating the input on the nsqd side. This is usually a
     * temporary error and can be caused by topics being added, deleted or cleared.
     */
    public const E_DPUB_FAILED = true;
    /**
     * This error may happen if a error condition is met after validating the input on the nsqd side. This can
     * happen in particular for messages that are no longer queued on the server side.
     */
    public const E_FIN_FAILED = false;
    /**
     * This error may happen if a error condition is met after validating the input on the nsqd side. This can
     * happen in particular for messages that are no longer queued on the server side.
     */
    public const E_REQ_FAILED = false;
    /**
     * This error may happen if a error condition is met after validating the input on the nsqd side. This can
     * happen in particular for messages that are no longer queued on the server side.
     */
    public const E_TOUCH_FAILED = false;
    /**
     * This error indicates that the authorization of the client failed on the server side. This might be related
     * to connection issues to the authorization server. Depending on the authorization server implementation, this
     * might also indicate that the given auth secret in the [ClientConfig] is not known on the server or the server
     * denied authentication with the current connection properties (i.e. TLS status and IP).
     */
    public const E_AUTH_FAILED = true;
    /**
     * This error happens if something breaks on the nsqd side while performing the authorization. This might be
     * caused by bugs in nsqd, the authorization server or network issues.
     */
    public const E_AUTH_ERROR = true;
    /**
     * This error is sent by nsqd if the client attempts an authentication, but the server does not support it. This
     * should never happen using this library as authorization requests are only sent if the server supports it.
     * It is safe to expect that this error is never thrown.
     */
    public const E_AUTH_DISABLED = true;
    /**
     * This error indicates that the client related to the authorization secret set in the [ClientConfig] is not
     * allowed to do the operation it tried to do.
     */
    public const E_UNAUTHORIZED = true;

    public static function terminable(Serendipity\Job\Kernel\Nsq\Frame\Error $error): bool
    {
        $type = explode(' ', $error->data)[0];

        $constant = 'self::'.$type;

        return \defined($constant) ? \constant($constant) : self::E_INVALID;
    }
}
