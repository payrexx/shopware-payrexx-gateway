<?php

/**
 * Transaction response model
 *
 * @copyright   Payrexx AG
 * @author      Payrexx Development Team <info@payrexx.com>
 */
namespace Payrexx\Models\Response;

/**
 * Transaction class
 *
 * @package Payrexx\Models\Response
 */
class Transaction extends \Payrexx\Models\Request\Transaction
{

    private $uuid;
    private $time;
    private $status;
    private $lang;
    private $psp;
    private $pspId;
    private $mode;
    private $payment;
    private $invoice;
    private $contact;
    private $pageUuid;

    /**
     * @access  public
     * @param   string  $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @access  public
     * @return  string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @access  public
     * @param   string  $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @access  public
     * @return  string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @access  public
     * @param   string  $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @access  public
     * @return  string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @access  public
     * @param   string  $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * @access  public
     * @return  string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @access  public
     * @param   string  $psp
     */
    public function setPsp($psp)
    {
        $this->psp = $psp;
    }

    /**
     * @access  public
     * @return  string
     */
    public function getPsp()
    {
        return $this->psp;
    }

    /**
     * @return int
     */
    public function getPspId()
    {
        return $this->pspId;
    }

    /**
     * @param int $pspId
     */
    public function setPspId($pspId)
    {
        $this->pspId = $pspId;
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @access  public
     * @param   array  $payment
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    /**
     * @access  public
     * @return  array
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return array
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param array $invoice
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * @return array
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param array $contact
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
    }

    /**
     * @return string
     */
    public function getPageUuid()
    {
        return $this->pageUuid;
    }

    /**
     * @param string $pageUuid
     */
    public function setPageUuid($pageUuid)
    {
        $this->pageUuid = $pageUuid;
    }
}
