<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Src\Application\UseCases\User\GetUserDataUseCase;
use Src\Application\UseCases\DTO\User\GetUserDataInputDto;
use Src\Domain\Exceptions\BusinessException;

class UserController extends Controller
{

    private GetUserDataUseCase $useCase;

    public function __construct(GetUserDataUseCase $useCase)
    {
        $this->useCase = $useCase;
    }
    public function show()
    {
        try {
            $id = 1;

            $input = new GetUserDataInputDto($id);
            $output = $this->useCase->execute($input);

            return response()->json([
                'user' => $output->user,
            ], Response::HTTP_OK);

        } catch (BusinessException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
