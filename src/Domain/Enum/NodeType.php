<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Enum;

enum NodeType: string
{
    case Input = 'input';
    case Prompt = 'prompt';
    case Validator = 'validator';
    case Condition = 'condition';
    case Transform = 'transform';
    case TextToSpeech = 'text_to_speech';
    case Transcription = 'transcription';
    case ImageGeneration = 'image_generation';
    case VideoRender = 'video_render';
    case Merge = 'merge';
    case FinalOutput = 'final_output';
    case HumanReview = 'human_review';
}
