<?php

namespace Payrexx\Models\Request;

use CURLFile;
use Payrexx\Models\Base;

/**
 * Design request class
 *
 * @copyright Payrexx AG
 * @author    Payrexx Development Team <info@payrexx.com>
 * @package   \Payrexx\Models\Request
 */
class Design extends Base
{
    /** @var string $uuid */
    protected $uuid;

    /** @var int $default */
    protected $default = 0;

    /** @var string $name */
    protected $name;

    /** @var string $fontFamily */
    protected $fontFamily;

    /** @var string $fontSize */
    protected $fontSize;

    /** @var string $textColor */
    protected $textColor;

    /** @var string $textColorVPOS */
    protected $textColorVPOS;

    /** @var string $linkColor */
    protected $linkColor;

    /** @var string $linkHoverColor */
    protected $linkHoverColor;

    /** @var string $buttonColor */
    protected $buttonColor;

    /** @var string $buttonHoverColor */
    protected $buttonHoverColor;

    /** @var string $background */
    protected $background;

    /** @var string $backgroundColor */
    protected $backgroundColor;

    /** @var string $headerBackground */
    protected $headerBackground;

    /** @var string $headerBackgroundColor */
    protected $headerBackgroundColor;

    /** @var string $emailHeaderBackgroundColor */
    protected $emailHeaderBackgroundColor;

    /** @var string $headerImageShape */
    protected $headerImageShape;

    /** @var int $useIndividualEmailLogo */
    protected $useIndividualEmailLogo = 0;

    /** @var string $logoBackgroundColor */
    protected $logoBackgroundColor;

    /** @var string $logoBorderColor */
    protected $logoBorderColor;

    /** @var string $VPOSGradientColor1 */
    protected $VPOSGradientColor1;

    /** @var string $VPOSGradientColor2 */
    protected $VPOSGradientColor2;

    /** @var int $enableRoundedCorners */
    protected $enableRoundedCorners;

    /** @var string $VPOSBackground */
    protected $VPOSBackground;

    /** @var CURLFile $headerImage */
    protected $headerImage;

    /** @var CURLFile $backgroundImage */
    protected $backgroundImage;

    /** @var CURLFile $headerBackgroundImage */
    protected $headerBackgroundImage;

    /** @var CURLFile $emailHeaderImage */
    protected $emailHeaderImage;
    protected $offset;
    protected $limit;

    /** @var CURLFile $VPOSBackgroundImage */
    protected $VPOSBackgroundImage;

    /**
     * {@inheritdoc}
     */
    public function getResponseModel()
    {
        return new \Payrexx\Models\Response\Design();
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return (bool)$this->default;
    }

    /**
     * @param bool $default
     */
    public function setDefault(bool $default)
    {
        $this->default = (int)$default;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getFontFamily()
    {
        return $this->fontFamily;
    }

    /**
     * @param string $fontFamily
     */
    public function setFontFamily(string $fontFamily)
    {
        $this->fontFamily = $fontFamily;
    }

    /**
     * @return string
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * @param string $fontSize
     */
    public function setFontSize(string $fontSize)
    {
        $this->fontSize = $fontSize;
    }

    /**
     * @return string
     */
    public function getTextColor()
    {
        return $this->textColor;
    }

    /**
     * @param string $textColor
     */
    public function setTextColor(string $textColor)
    {
        $this->textColor = $textColor;
    }

    /**
     * @return string
     */
    public function getTextColorVPOS()
    {
        return $this->textColorVPOS;
    }

    /**
     * @param string $textColorVPOS
     */
    public function setTextColorVPOS(string $textColorVPOS)
    {
        $this->textColorVPOS = $textColorVPOS;
    }

    /**
     * @return string
     */
    public function getLinkColor()
    {
        return $this->linkColor;
    }

    /**
     * @param string $linkColor
     */
    public function setLinkColor(string $linkColor)
    {
        $this->linkColor = $linkColor;
    }

    /**
     * @return string
     */
    public function getLinkHoverColor()
    {
        return $this->linkHoverColor;
    }

    /**
     * @param string $linkHoverColor
     */
    public function setLinkHoverColor(string $linkHoverColor)
    {
        $this->linkHoverColor = $linkHoverColor;
    }

    /**
     * @return string
     */
    public function getButtonColor()
    {
        return $this->buttonColor;
    }

    /**
     * @param string $buttonColor
     */
    public function setButtonColor(string $buttonColor)
    {
        $this->buttonColor = $buttonColor;
    }

    /**
     * @return string
     */
    public function getButtonHoverColor()
    {
        return $this->buttonHoverColor;
    }

    /**
     * @param string $buttonHoverColor
     */
    public function setButtonHoverColor(string $buttonHoverColor)
    {
        $this->buttonHoverColor = $buttonHoverColor;
    }

    /**
     * @return string
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * @param string $background
     */
    public function setBackground(string $background)
    {
        $this->background = $background;
    }

    /**
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     */
    public function setBackgroundColor(string $backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;
    }

    /**
     * @return string
     */
    public function getHeaderBackground()
    {
        return $this->headerBackground;
    }

    /**
     * @param string $headerBackground
     */
    public function setHeaderBackground(string $headerBackground)
    {
        $this->headerBackground = $headerBackground;
    }

    /**
     * @return string
     */
    public function getHeaderBackgroundColor()
    {
        return $this->headerBackgroundColor;
    }

    /**
     * @param string $headerBackgroundColor
     */
    public function setHeaderBackgroundColor(string $headerBackgroundColor)
    {
        $this->headerBackgroundColor = $headerBackgroundColor;
    }

    /**
     * @return string
     */
    public function getEmailHeaderBackgroundColor()
    {
        return $this->emailHeaderBackgroundColor;
    }

    /**
     * @param string $emailHeaderBackgroundColor
     */
    public function setEmailHeaderBackgroundColor(string $emailHeaderBackgroundColor)
    {
        $this->emailHeaderBackgroundColor = $emailHeaderBackgroundColor;
    }

    /**
     * @return string
     */
    public function getHeaderImageShape()
    {
        return $this->headerImageShape;
    }

    /**
     * @param string $headerImageShape
     */
    public function setHeaderImageShape(string $headerImageShape)
    {
        $this->headerImageShape = $headerImageShape;
    }

    /**
     * @return bool
     */
    public function isUseIndividualEmailLogo()
    {
        return (bool)$this->useIndividualEmailLogo;
    }

    /**
     * @param bool $useIndividualEmailLogo
     */
    public function setUseIndividualEmailLogo(bool $useIndividualEmailLogo)
    {
        $this->useIndividualEmailLogo = (int)$useIndividualEmailLogo;
    }

    /**
     * @return string
     */
    public function getLogoBackgroundColor()
    {
        return $this->logoBackgroundColor;
    }

    /**
     * @param string $logoBackgroundColor
     */
    public function setLogoBackgroundColor(string $logoBackgroundColor)
    {
        $this->logoBackgroundColor = $logoBackgroundColor;
    }

    /**
     * @return string
     */
    public function getLogoBorderColor()
    {
        return $this->logoBorderColor;
    }

    /**
     * @param string $logoBorderColor
     */
    public function setLogoBorderColor(string $logoBorderColor)
    {
        $this->logoBorderColor = $logoBorderColor;
    }

    /**
     * @return string
     */
    public function getVPOSGradientColor1()
    {
        return $this->VPOSGradientColor1;
    }

    /**
     * @param string $VPOSGradientColor1
     */
    public function setVPOSGradientColor1(string $VPOSGradientColor1)
    {
        $this->VPOSGradientColor1 = $VPOSGradientColor1;
    }

    /**
     * @return string
     */
    public function getVPOSGradientColor2()
    {
        return $this->VPOSGradientColor2;
    }

    /**
     * @param string $VPOSGradientColor2
     */
    public function setVPOSGradientColor2(string $VPOSGradientColor2)
    {
        $this->VPOSGradientColor2 = $VPOSGradientColor2;
    }

    /**
     * @return bool
     */
    public function isEnableRoundedCorners()
    {
        return (bool)$this->enableRoundedCorners;
    }

    /**
     * @param bool $enableRoundedCorners
     */
    public function setEnableRoundedCorners(bool $enableRoundedCorners)
    {
        $this->enableRoundedCorners = (int)$enableRoundedCorners;
    }

    /**
     * @return string
     */
    public function getHeaderImage()
    {
        return $this->headerImage;
    }

    /**
     * @param CURLFile $headerImage
     */
    public function setHeaderImage($headerImage)
    {
        $this->headerImage = $headerImage;
    }

    /**
     * @return string
     */
    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    /**
     * @param CURLFile $backgroundImage
     */
    public function setBackgroundImage($backgroundImage)
    {
        $this->backgroundImage = $backgroundImage;
    }

    /**
     * @return string
     */
    public function getHeaderBackgroundImage()
    {
        return $this->headerBackgroundImage;
    }

    /**
     * @param CURLFile $headerBackgroundImage
     */
    public function setHeaderBackgroundImage($headerBackgroundImage)
    {
        $this->headerBackgroundImage = $headerBackgroundImage;
    }

    /**
     * @return string
     */
    public function getEmailHeaderImage()
    {
        return $this->emailHeaderImage;
    }

    /**
     * @param CURLFile $emailHeaderImage
     */
    public function setEmailHeaderImage($emailHeaderImage)
    {
        $this->emailHeaderImage = $emailHeaderImage;
    }

    /**
     * @return string
     */
    public function getVPOSBackground()
    {
        return $this->VPOSBackground;
    }

    /**
     * @param string $VPOSBackground
     */
    public function setVPOSBackground($VPOSBackground)
    {
        $this->VPOSBackground = $VPOSBackground;
    }

    /**
     * @return CURLFile
     */
    public function getVPOSBackgroundImage()
    {
        return $this->VPOSBackgroundImage;
    }

    /**
     * @param CURLFile $VPOSBackgroundImage
     */
    public function setVPOSBackgroundImage($VPOSBackgroundImage)
    {
        $this->VPOSBackgroundImage = $VPOSBackgroundImage;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }
}
