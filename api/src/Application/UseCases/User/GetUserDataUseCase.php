<?php

namespace Src\Application\UseCases\User;

use Src\Application\UseCases\DTO\User\GetUserDataInputDto;
use Src\Application\UseCases\DTO\User\GetUserDataOutputDto;
use Src\Infra\Eloquent\UserModel;
use Src\Domain\Exceptions\BusinessException;

class GetUserDataUseCase
{
    /**
     * @throws BusinessException
     */
    public function execute(GetUserDataInputDto $input): GetUserDataOutputDto
    {
        $user = UserModel::find($input->userId);

        if (!$user) {
            throw new BusinessException('Usuário não encontrado.');
        }

        return new GetUserDataOutputDto($user->toArray());
    }
}
